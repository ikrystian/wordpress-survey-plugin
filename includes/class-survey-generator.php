<?php
class SurveyGenerator {
    public function __construct() {
        add_action('init', [$this, 'register_survey_post_type']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);

        add_action('rest_api_init', function () {
            register_rest_route('my-plugin/v1', '/surveys', array(
                'methods' => 'GET',
                'callback' => 'get_surveys',
            ));
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