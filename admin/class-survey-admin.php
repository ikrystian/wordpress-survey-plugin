<?php

class SurveyAdminPage
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'handle_survey_deletion']);

        add_action('admin_menu', [$this, 'add_survey_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_save_survey', [$this, 'save_survey']);
        add_action('wp_ajax_delete_survey', [$this, 'delete_survey']);
    }

    public function add_survey_menu()
    {
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

    public function survey_list_page()
    {
        $surveys = get_posts(['post_type' => 'survey']);
        include plugin_dir_path(__FILE__) . 'templates/survey-list.php';
    }

    public function add_survey_page()
    {
        include plugin_dir_path(__FILE__) . 'templates/survey-form.php';
    }

    public function enqueue_admin_scripts($hook)
    {
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

//            wp_enqueue_style('survey-styles', plugin_dir_url(__FILE__) . 'assets/css/survey-styles.css');
            wp_enqueue_script('survey-script', plugin_dir_url(__FILE__) . 'assets/js/survey-script.js', ['jquery'], '1.0', true);

            wp_localize_script('survey-script', 'surveyAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('survey_admin_nonce')
            ]);
        }
    }

    public function handle_survey_deletion() {
        if (isset($_POST['delete_survey_id'])) {
            $survey_id = intval($_POST['delete_survey_id']);

            // Usuń ankietę
            wp_delete_post($survey_id, true); // Usuwa post (ankietę) z bazy danych

            // Usuń pytania z metadanych
            delete_post_meta($survey_id, 'survey_questions');

            // Usuń statystyki kliknięć
            global $wpdb;
            $table_name = $wpdb->prefix . 'survey_clicks';
            $wpdb->delete($table_name, ['survey_id' => $survey_id]);

            // Przekierowanie po usunięciu
            wp_redirect(admin_url('admin.php?page=survey-generator'));
            exit;
        }
    }

    public function save_survey()
    {
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

    public function delete_survey()
    {
        check_ajax_referer('survey_admin_nonce', 'nonce');

        $survey_id = intval($_POST['survey_id']);

        if (wp_delete_post($survey_id, true)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Nie udało się usunąć ankiety');
        }
    }


    public function display_survey_statistics() {
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

        global $wpdb;
        $surveys = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->prefix}posts WHERE post_type = 'survey' AND post_status = 'publish'");

        echo '<h1>Statystyki Ankiet</h1>';
        echo '<h2>Lista Ankiet</h2>';
        echo '<ul>';
        foreach ($surveys as $survey) {
            echo '<li><a href="?page=survey-statistics&survey_id=' . $survey->ID . '">' . esc_html($survey->post_title) . '</a></li>';
        }
        echo '</ul>';

        // Sprawdzenie, czy wybrano ankietę
        if (isset($_GET['survey_id'])) {
            $this->display_survey_details($_GET['survey_id']);
        }
    }

    private function display_survey_details($survey_id) {
        global $wpdb;

        // Pobierz posty użytkowników dla danej ankiety
        $user_progress = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, progress FROM {$wpdb->prefix}survey_user_progress WHERE survey_id = %d",
            $survey_id
        ));

        if ($user_progress) {
            echo '<h2>Statystyki Ankiety</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>
            <tr>
                <th>ID Użytkownika</th>
                <th>Odpowiedzi</th>
                <th>Czas Odpowiedzi</th>
            </tr>
          </thead>
          <tbody>';

            $answer_counts = []; // Tablica do zliczania odpowiedzi
            $total_time = 0; // Zmienna do zliczania czasu odpowiedzi
            $response_count = count($user_progress); // Liczba odpowiedzi

            foreach ($user_progress as $progress) {
                $responses = unserialize($progress->progress);
                $user_id = esc_html($progress->user_id);
                $user_answers = [];

                echo '<tr>';
                echo '<td>' . $user_id . '</td>';
                echo '<td>';
                foreach ($responses['results'] as $response) {
                    echo 'Pytanie<strong>' . esc_html($response['question']) . '</strong><br>' . esc_html($response['answerText']) . '<br><br>';
                    $user_answers[] = $response['answerText']; // Zbieranie odpowiedzi użytkownika
                }
                echo '</td>';

                // Zbieranie czasu odpowiedzi (przykładowo, można dodać czas w progress)
                $time_taken = isset($responses['time_taken']) ? $responses['time_taken'] : 0; // Czas odpowiedzi
                $total_time += $time_taken; // Zliczanie całkowitego czasu
                echo '<td>' . esc_html($time_taken) . ' sekundy</td>';

                // Zliczanie odpowiedzi
                foreach ($user_answers as $answer) {
                    if (!isset($answer_counts[$answer])) {
                        $answer_counts[$answer] = 0;
                    }
                    $answer_counts[$answer]++;
                }

                echo '</tr>';
            }

            echo '</tbody></table>';

            // Wyświetlanie statystyk zbiorczych
            echo '<h3>Statystyki Zbiorcze</h3>';
            echo '<p>Średni czas odpowiedzi: ' . round($total_time / $response_count, 2) . ' sekundy</p>';
            echo '<h4>Najczęściej Wybierane Odpowiedzi:</h4>';
            echo '<ul>';
            arsort($answer_counts); // Sortowanie odpowiedzi według liczby
            foreach ($answer_counts as $answer => $count) {
                echo '<li>' . esc_html($answer) . ': ' . $count . ' razy</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Brak danych statystycznych dla tej ankiety.</p>';
        }
    }


}

function render_page_select() {
    // Użyj WordPress REST API do pobrania stron
    $response = wp_remote_get(rest_url('wp/v2/pages'));
    print_r( $response);
    if (is_wp_error($response)) {
        return '<p>Nie można pobrać stron.</p>';
    }

    $pages = json_decode(wp_remote_retrieve_body($response), true);

    // Tworzenie pola select
    $output = '<select id="page-select" name="page_id">';
    $output .= '<option value="">Wybierz stronę</option>'; // Opcja domyślna

    foreach ($pages as $page) {
        $output .= '<option value="' . esc_attr($page['id']) . '">' . esc_html($page['title']['rendered']) . '</option>';
    }

    $output .= '</select>';

    return $output;
}

