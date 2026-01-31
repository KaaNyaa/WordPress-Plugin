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

// Shortcode to display volunteer opportunities
add_shortcode('volunteer', 'vol_plugin_shortcode_handler');
function vol_plugin_shortcode_handler($atts) {
    // Logic for parameters: hours and type
    // Logic for conditional row colors (Green < 10, Yellow 10-100, Red > 100)
    return "Volunteer Opportunity Table will render here.";
}

function vol_plugin_shortcode_handler($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'volunteer_opportunities';

    $atts = shortcode_atts( array(
        'hours' => '',
        'type' => '',
    ), $atts );

    $query = "SELECT * FROM $table_name WHERE 1=1";
    if (!empty($atts['hours'])) {
        $query .= $wpdb->prepare(" AND hours <= %d", $atts['hours']);
    }
    if (!empty($atts['type'])) {
        $query .= $wpdb->prepare(" AND type = %s", $atts['type']);
    }

    $results = $wpdb->get_results($query);

    $output = '<table style="width:100%; border-collapse: collapse;">';
    $output .= '<tr><th>Position</th><th>Org</th><th>Type</th><th>Hours</th><th>Email</th></tr>';

    foreach ($results as $row) {
        $bg_color = 'transparent';

        if (empty($atts['hours']) && empty($atts['type'])) {
            if ($row->hours < 10) {
                $bg_color = '#d4edda'; // light green
            } elseif ($row->hours <= 100) {
                $bg_color = '#fff3cd'; // light yellow
            } else {
                $bg_color = '#f8d7da'; // light red
            }
        }
        $output .= "<tr style='background-color: $bg_color; border: 1px solid #ddd;'>";
        $output .= "<td><strong>$row->position</strong></td>";
        $output .= "<td>$row->organization</td>";
        $output .= "<td>$row->type</td>";
        $output .= "<td>$row->hours</td>";
        $output .= "<td>$row->email</td>";
        $output .= "</tr>";
    }
    $output .= '</table>';

    return $output;
}