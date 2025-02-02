<?php
class SurveyGenerator {
    public function __construct() {
        add_action('init', [$this, 'register_survey_post_type']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);

        add_action('wp_ajax_record_click', [$this, 'record_click']);
        add_action('wp_ajax_nopriv_record_click', [$this, 'record_click']);

        add_action('rest_api_init', function () {
            register_rest_route('my-plugin/v1', '/surveys', array(
                'methods' => 'GET',
                'callback' => 'get_surveys',
            ));
        register_activation_hook(__FILE__, 'create_survey_clicks_table');


            if (!isset($_COOKIE['user_id'])) {
                $user_id = uniqid('user_', true); // Generowanie unikalnego identyfikatora
                setcookie('user_id', $user_id, time() + (86400 * 30), "/"); // Ciasteczko ważne przez 30 dni
            }

        });


        function get_surveys() {
            // Fetch surveys from the database or any other source
            $surveys = array(
                array('id' => 1, 'title' => 'Customer Satisfaction Survey'),
                array('id' => 2, 'title' => 'Product Feedback Survey'),
            );

            return new WP_REST_Response($surveys, 200);
        }

    }


    public function record_click() {
        $survey_id = intval($_POST['survey_id']);
        $question_id = intval($_POST['question_id']);
        $answer_text = sanitize_text_field($_POST['answer_text']);
        $user_ip = $_SERVER['REMOTE_ADDR']; // Zbieranie IP użytkownika
        $user_agent = $_SERVER['HTTP_USER_AGENT']; // Zbieranie User-Agent
        $user_id = sanitize_text_field($_POST['user_id']); // Odbieranie identyfikatora użytkownika


        // Zapisz dane do bazy danych
        $click_data = [
            'survey_id' => $survey_id,
            'question_id' => $question_id,
            'answer_text' => $answer_text,
            'user_ip' => $user_ip,
            'user_agent' => $user_agent,
            'user_id' => $user_id, // Zapisz identyfikator użytkownika
            'timestamp' => current_time('mysql'),
        ];

        global $wpdb;
        $wpdb->insert('wp_survey_clicks', $click_data); // Upewnij się, że tabela istnieje

        wp_send_json_success();
    }


    public function register_survey_post_type() {
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

        register_block_type( __DIR__ . '../blocks/survey/build' );

    }

    public function create_survey_clicks_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_clicks';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        survey_id mediumint(9) NOT NULL,
        question_id mediumint(9) NOT NULL,
        answer_text text NOT NULL,
        user_id varchar(255) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }



    public function enqueue_frontend_scripts() {
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