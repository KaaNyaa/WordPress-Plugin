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

// Admin Page Content
function vol_plugin_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'volunteer_opportunities';
    // Handle form submission
    if (isset($_POST['save_volunteer'])) {
        // Sanitize and validate input
        $position = sanitize_text_field($_POST['position']);
        $email = sanitize_email($_POST['email']);
        $hours = intval($_POST['hours']);
        // Insert into database
        if (!empty($position) && is_email($email)) {
            $wpdb->insert($table_name, array(
                'position' => $position,
                'organization' => sanitize_text_field($_POST['organization']),
                'type' => sanitize_text_field($_POST['type']),
                'email' => $email,
                'description' => sanitize_textarea_field($_POST['description']),
                'location' => sanitize_text_field($_POST['location']),
                'hours' => $hours,
                'skills' => sanitize_textarea_field($_POST['skills']),
            ));
            echo '<div class="updated"><p>Opportunity Saved!</p></div>';
        } else {
            echo '<div class="error"><p>Please provide a valid position and email.</p></div>';
        }
    }
    // Render the form
    ?>
    <div class="wrap">
        <h1>Volunteer Opportunities Manager</h1>

        <h2>Add New Opportunity</h2>
        <form method="post">
            <table class="form-table">
                <tr><th>Position</th><td><input name="position" type="text" required /></td></tr>
                <tr><th>Organization</th><td><input name="organization" type="text" /></td></tr>
                <tr><th>Type</th><td>
                    <select name="type">
                        <option value="one-time">One-time</option>
                        <option value="recurring">Recurring</option>
                        <option value="seasonal">Seasonal</option>
                    </select>
                </td></tr>
                <tr><th>Email</th><td><input name="email" type="email" required /></td></tr>
                <tr><th>Hours</th><td><input name="hours" type="number" /></td></tr>
                <tr><th>Description</th><td><textarea name="description"></textarea></td></tr>
            </table>
            <input type="submit" name="save_volunteer" class="button button-primary" value="Save Opportunity">
        </form>
    </div>
    <?php     
}

// Shortcode to display volunteer opportunities
add_shortcode('volunteer', 'vol_plugin_shortcode_handler');
function vol_plugin_shortcode_handler($atts) {
    // Logic for parameters: hours and type
    // Logic for conditional row colors (Green < 10, Yellow 10-100, Red > 100)
    return "Volunteer Opportunity Table will render here.";
}

// Shortcode to display volunteer opportunities
function vol_plugin_shortcode_handler($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'volunteer_opportunities';
    // Handle shortcode attributes
    $atts = shortcode_atts( array(
        'hours' => '',
        'type' => '',
    ), $atts );
    // Build query based on attributes
    $query = "SELECT * FROM $table_name WHERE 1=1";
    if (!empty($atts['hours'])) {
        $query .= $wpdb->prepare(" AND hours <= %d", $atts['hours']);
    }
    if (!empty($atts['type'])) {
        $query .= $wpdb->prepare(" AND type = %s", $atts['type']);
    }
    // Fetch results
    $results = $wpdb->get_results($query);
    // Generate HTML table
    $output = '<table style="width:100%; border-collapse: collapse;">';
    $output .= '<tr><th>Position</th><th>Org</th><th>Type</th><th>Hours</th><th>Email</th></tr>';
    // Loop through results and apply conditional coloring
    foreach ($results as $row) {
        $bg_color = 'transparent';
        // Determine background color based on hours if no filters are applied
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
    // Return the generated table
    return $output;
}