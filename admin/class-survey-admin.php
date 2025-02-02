<?php

class SurveyAdminPage
{
    public function __construct()
    {
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

            wp_enqueue_style('survey-styles', plugin_dir_url(__FILE__) . 'assets/css/survey-styles.css');
            wp_enqueue_script('survey-script', plugin_dir_url(__FILE__) . 'assets/js/survey-script.js', ['jquery'], '1.0', true);

            wp_localize_script('survey-script', 'surveyAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('survey_admin_nonce')
            ]);
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
        $table_name = $wpdb->prefix . 'survey_clicks';

        // Pobierz pytania dla danej ankiety
        $questions = get_post_meta($survey_id, 'survey_questions', true);

        echo '<h2>Szczegóły Ankiety: ' . esc_html(get_the_title($survey_id)) . '</h2>';

        // Przygotowanie danych do wykresu
        $chartData = [];
        $chartLabels = [];

        foreach ($questions as $index => $question) {
            // Pobierz kliknięcia dla danego pytania
            $clicks = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE survey_id = %d AND question_id = %d", $survey_id, $index));

            // Zlicz odpowiedzi
            $answerCounts = [];
            foreach ($clicks as $click) {
                if (!isset($answerCounts[$click->answer_text])) {
                    $answerCounts[$click->answer_text] = 0;
                }
                $answerCounts[$click->answer_text]++;
            }

            // Dodaj dane do wykresu
            $chartLabels[] = esc_html($question['question']);
            $chartData[] = array_values($answerCounts);
        }

        // Wyświetlenie wykresu
        echo '<canvas id="surveyChart" width="400" height="200"></canvas>';
        echo '<script>
        var ctx = document.getElementById("surveyChart").getContext("2d");
        var chart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: ' . json_encode($chartLabels) . ',
                datasets: ' . json_encode($this->prepareChartDatasets($chartData)) . '
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>';
    }

    private function prepareChartDatasets($chartData) {
        $datasets = [];
        $colors = ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)', 'rgba(75, 192, 192, 0.2)'];

        foreach ($chartData as $index => $data) {
            $datasets[] = [
                'label' => 'Pytanie ' . ($index + 1),
                'data' => $data,
                'backgroundColor' => $colors[$index % count($colors)],
                'borderColor' => $colors[$index % count($colors)],
                'borderWidth' => 1
            ];
        }

        return $datasets;
    }


}