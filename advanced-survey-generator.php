<?php
/*
Plugin Name: Advanced Survey Generator
Description: Generate surveys with visual editor.
Version: 1.0
Author: ArchCoders limited.
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-survey-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-survey-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-survey-admin.php';

class AdvancedSurveyGenerator {
    public function __construct() {
        $survey_generator = new SurveyGenerator();
        $survey_admin = new SurveyAdminPage();
        $survey_shortcode = new SurveyShortcode();
    }
}

new AdvancedSurveyGenerator();

register_activation_hook(__FILE__, 'create_survey_clicks_table');

function create_survey_clicks_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'survey_clicks';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        survey_id mediumint(9) NOT NULL,
        question_id mediumint(9) NOT NULL,
        answer_text text NOT NULL,
        user_ip varchar(100) NOT NULL,
        user_id varchar(255) NOT NULL,
        user_agent text NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}