<?php
class SurveyAdminPage {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_survey_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_save_survey', [$this, 'save_survey']);
        add_action('wp_ajax_delete_survey', [$this, 'delete_survey']);
    }

    public function add_survey_menu() {
        add_menu_page(
            'Survey Generator',
            'Survey Generator',
            'manage_options',
            'survey-generator',
            [$this, 'survey_list_page'],
            'dashicons-clipboard'

        );

        add_submenu_page(
            'survey-generator',
            'Dodaj Ankietę',
            'Dodaj Ankietę',
            'manage_options',
            'add-survey',
            [$this, 'add_survey_page'],
            'dashicons-clipboard'
        );

        add_submenu_page(
            'survey-generator',
            'Statystyki Ankiet',
            'Statystyki Ankiet',
            'manage_options',
            'survey-statistics',
            [$this, 'display_survey_statistics'],
            'dashicons-chart-bar'
        );
    }

    public function survey_list_page() {
        $surveys = get_posts(['post_type' => 'survey']);
        include plugin_dir_path(__FILE__) . 'templates/survey-list.php';
    }

    public function add_survey_page() {
        include plugin_dir_path(__FILE__) . 'templates/survey-form.php';
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'survey-generator') !== false) {
            wp_enqueue_script('survey-script',
                plugin_dir_url(__FILE__) . '../assets/js/survey-script.js',
                ['jquery'],
                '1.0',
                true
            );

            wp_enqueue_style('survey-admin-style',
                plugin_dir_url(__FILE__) . '../assets/css/admin-style.css'
            );

            wp_enqueue_style('survey-styles', plugin_dir_url(__FILE__) . 'assets/css/survey-styles.css');
            wp_enqueue_script('survey-script', plugin_dir_url(__FILE__) . 'assets/js/survey-script.js', ['jquery'], '1.0', true);

            wp_localize_script('survey-script', 'surveyAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('survey_admin_nonce')
            ]);
        }
    }

    public function save_survey() {
        check_ajax_referer('survey_admin_nonce', 'nonce');

        $survey_data = json_decode(stripslashes($_POST['survey_data']), true);
        $survey_title = sanitize_text_field($_POST['survey_title']);
        $survey_id = intval($_POST['survey_id']);

        $survey_post = [
            'post_title' => $survey_title,
            'post_type' => 'survey',
            'post_status' => 'publish'
        ];

        if ($survey_id) {
            $survey_post['ID'] = $survey_id;
            wp_update_post($survey_post);
        } else {
            $survey_id = wp_insert_post($survey_post);
        }

        update_post_meta($survey_id, 'survey_questions', $survey_data);

        wp_send_json_success($survey_id);
    }

    public function delete_survey() {
        check_ajax_referer('survey_admin_nonce', 'nonce');

        $survey_id = intval($_POST['survey_id']);

        if (wp_delete_post($survey_id, true)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Nie udało się usunąć ankiety');
        }
    }



    public function display_survey_statistics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_clicks';

        $results = $wpdb->get_results("SELECT * FROM $table_name");

        // Wyświetlanie wyników w formie tabeli
        echo '<h1>Statystyki Ankiet</h1>';
        echo '<table>';
        echo '<tr><th>ID Ankiety</th><th>ID Pytania</th><th>Odpowiedź</th><th>IP Użytkownika</th><th>User Agent</th><th>Czas</th></tr>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->survey_id) . '</td>';
            echo '<td>' . esc_html($row->question_id) . '</td>';
            echo '<td>' . esc_html($row->answer_text) . '</td>';
            echo '<td>' . esc_html($row->user_ip) . '</td>';
            echo '<td>' . esc_html($row->user_agent) . '</td>';
            echo '<td>' . esc_html($row->timestamp) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }


}