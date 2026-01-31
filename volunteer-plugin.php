<?php
/**
 * Plugin Name: Volunteer Opportunity Plugin
 * Description: A plugin for listing and managing volunteer opportunities.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit; // Safety first!

global $wpdb;
$table_name = $wpdb->prefix . 'volunteer_opportunities';

register_activation_hook(__FILE__, 'vol_plugin_create_db');
function vol_plugin_create_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'volunteer_opportunities';
    $charset_collate = $wpdb->get_charset_collate();

    // MySQL Table Schema
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        position varchar(255) NOT NULL,
        organization varchar(255) NOT NULL,
        type varchar(50) NOT NULL,
        email varchar(100) NOT NULL,
        description text NOT NULL,
        location varchar(255) NOT NULL,
        hours int(10) NOT NULL,
        skills text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Admin Menu
add_action('admin_menu', 'vol_plugin_setup_menu');
function vol_plugin_setup_menu() {
    add_menu_page('Volunteer Opportunities', 'Volunteer', 'manage_options', 'volunteer-plugin', 'vol_plugin_admin_page', 'dashicons-groups');
}

function vol_plugin_admin_page() {
    echo '<div class="wrap"><h1>Volunteer Opportunities</h1><p>Manage your listings here.</p></div>';
}