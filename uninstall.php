<?php
// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get the global database object
global $wpdb;

// Define the table name
$table_name = $wpdb->prefix . 'survey_clicks';

// Delete the table
$wpdb->query("DROP TABLE IF EXISTS $table_name");