<?php

class SurveyGenerator
{
    public function __construct()
    {
        add_action('init', [$this, 'register_survey_post_type']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('wp_ajax_save_user_progress', [$this, 'handle_save_user_progress']);

        add_action('wp_ajax_record_click', [$this, 'record_click']);
        add_action('wp_ajax_nopriv_record_click', [$this, 'record_click']);

        add_action('rest_api_init', function () {
            register_rest_route('my-plugin/v1', '/surveys', array(
                'methods' => 'GET',
                'callback' => 'get_surveys',
            ));


            if (!isset($_COOKIE['user_id'])) {
                $user_id = uniqid('user_', true); // Generowanie unikalnego identyfikatora
                setcookie('user_id', $user_id, time() + (86400 * 30), "/"); // Ciasteczko ważne przez 30 dni
            }

        });


        function get_surveys()
        {
            // Fetch surveys from the database or any other source
            $surveys = array(
                array('id' => 1, 'title' => 'Customer Satisfaction Survey'),
                array('id' => 2, 'title' => 'Product Feedback Survey'),
            );

            return new WP_REST_Response($surveys, 200);
        }

    }
    public function save_user_progress($user_id, $survey_id, $progress_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_user_progress'; // Create a new table for user progress

        // Check if the user progress already exists
        $existing_progress = $wpdb->get_var($wpdb->prepare("SELECT progress FROM $table_name WHERE user_id = %s AND survey_id = %d", $user_id, $survey_id));
        // Serialize the progress data
        $serialized_data = serialize($progress_data);

        if ($existing_progress) {
            // Update existing progress
            $data = $wpdb->update($table_name, ['progress' => $serialized_data], ['user_id' => $user_id, 'survey_id' => $survey_id]);
        } else {
            // Insert new progress
            $data = $wpdb->insert($table_name, ['user_id' => $user_id, 'survey_id' => $survey_id, 'progress' => $serialized_data]);
        }

        return $data;
    }

    public function handle_save_user_progress() {
        $user_id = sanitize_text_field($_POST['user_id']);
        $survey_id = intval($_POST['survey_id']);
        $progress_data = $_POST['progress_data']; // This should be an array
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $meta =  ['ip' => $user_ip, 'agent' => $user_agent, 'time' => date('Y-m-d H:i:s')];
        $results = $this->save_user_progress($user_id, $survey_id, $progress_data);
        return wp_send_json_success($results);
    }

    public function record_click()
    {
        $survey_id = intval($_POST['survey_id']);
        $answer_text = sanitize_text_field($_POST['answer_text']);
        $user_ip = $_SERVER['REMOTE_ADDR']; // Zbieranie IP użytkownika
        $user_agent = $_SERVER['HTTP_USER_AGENT']; // Zbieranie User-Agent
        $user_id = sanitize_text_field($_POST['user_id']); // Odbieranie identyfikatora użytkownika

        $this->save_user_progress($user_id, $survey_id, $answer_text );

    }


    public function register_survey_post_type()
    {
        register_post_type('survey', [
            'labels' => [
                'name' => 'Ankiety',
                'singular_name' => 'Ankieta'
            ],
            'show_in_menu' => false,
            'public' => true,
            'has_archive' => true,
            'supports' => ['title']
        ]);

        register_block_type(__DIR__ . '../blocks/survey/build');

    }


    public function enqueue_frontend_scripts()
    {
        wp_enqueue_script('survey-script',
            plugin_dir_url(__FILE__) . '../assets/js/survey-script.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_register_script(
            'my-custom-block-script',
            plugins_url('block.js', __FILE__), // Path to the JavaScript file
            array('wp-blocks', 'wp-element', 'wp-editor'), // Dependencies
            filemtime(plugin_dir_path(__FILE__) . 'block.js') // Version based on file modification time
        );

        // Register the block type
        register_block_type('my-plugin/my-custom-block', array(
            'editor_script' => 'my-custom-block-script',
        ));

        wp_enqueue_style('survey-frontend-style',
            plugin_dir_url(__FILE__) . '../assets/css/frontend-style.css'
        );
    }

}