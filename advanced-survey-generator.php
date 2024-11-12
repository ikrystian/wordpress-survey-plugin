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