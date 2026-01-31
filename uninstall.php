<?php 
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'volunteer_opportunities';

// Drop the custom table created by the plugin
$wpdb->query("DROP TABLE IF EXISTS $table_name");