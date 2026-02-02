<?php
/**
 * "StAuth10127: I Nick Micheletti, 000859568 certify that this material is my original work. 
 * No other person's work has been used without due acknowledgement. I have not made my work available to anyone else."
 * 
 * Plugin Name: Volunteer Opportunity Plugin
 * Description: A plugin for listing and managing volunteer opportunities.
 * Version: 1.1
 * Author: Nick Micheletti
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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

// Separate logic handler to fix "Headers Already Sent"
add_action('admin_init', 'vol_plugin_handle_logic');
function vol_plugin_handle_logic() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'volunteer_opportunities';

    // Handle deletion BEFORE any HTML output
    if (isset($_GET['page']) && $_GET['page'] == 'volunteer-plugin' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $wpdb->delete($table_name, array('id' => $id));
        
        // Safe redirect
        wp_redirect(admin_url('admin.php?page=volunteer-plugin&deleted=true'));
        exit;
    }
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

    $edit_record = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $edit_id = intval($_GET['id']);
        $edit_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
    }

    // Handle form submission
    if (isset($_POST['save_volunteer'])) {
        $data = array(
            'position'     => sanitize_text_field($_POST['position']),
            'organization' => sanitize_text_field($_POST['organization']),
            'type'         => sanitize_text_field($_POST['type']),
            'email'        => sanitize_email($_POST['email']),
            'description'  => sanitize_textarea_field($_POST['description']),
            'location'     => sanitize_text_field($_POST['location'] ?? ''),
            'hours'        => intval($_POST['hours']),
            'skills'       => sanitize_textarea_field($_POST['skills'] ?? ''),
        );

        if (isset($_GET['id']) && $edit_record) {
            // Update existing
            $wpdb->update($table_name, $data, array('id' => intval($_GET['id'])));
            echo '<div class="updated"><p>Opportunity Updated!</p></div>';
            // Refresh record for form autofill
            $edit_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['id'])));
        } else {
            // Insert new
            $wpdb->insert($table_name, $data);
            echo '<div class="updated"><p>Opportunity Saved!</p></div>';
        }
    }

    if (isset($_GET['deleted'])) {
        echo '<div class="updated"><p>Opportunity Deleted!</p></div>';
    }

    // Fetch existing opportunities
    $opportunities = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<h2>Existing Opportunities</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Position</th><th>Organization</th><th>Hours</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    foreach ($opportunities as $item) {
        // Generate delete URL
        $delete_url = admin_url('admin.php?page=volunteer-plugin&action=delete&id=' . $item->id);
        $edit_url = admin_url('admin.php?page=volunteer-plugin&action=edit&id=' . $item->id);

        echo "<tr>";
        echo "<td>" . esc_html($item->position) . "</td>";
        echo "<td>" . esc_html($item->organization) . "</td>";
        echo "<td>" . esc_html($item->hours) . "</td>";
        echo "<td>
                <a href='$edit_url' class='button' style='margin-right:5px;'>Edit</a>
                <a href='$delete_url' class='button' onclick='return confirm(\"Are you sure?\")'>Delete</a>
              </td>";
        echo "</tr>";
    }
    if (empty($opportunities)) {
        echo '<tr><td colspan="4">No opportunities found.</td></tr>';
    }
    
    echo '</tbody></table>';
    // Render the form
    ?>
    <div class="wrap">
        <h1>Volunteer Opportunities Manager</h1>
        <h2><?php echo $edit_record ? 'Edit Opportunity' : 'Add New Opportunity'; ?></h2>
        <form method="post">
            <table class="form-table">
                <tr><th>Position</th><td><input name="position" type="text" value="<?php echo $edit_record ? esc_attr($edit_record->position) : ''; ?>" required /></td></tr>
                <tr><th>Organization</th><td><input name="organization" type="text" value="<?php echo $edit_record ? esc_attr($edit_record->organization) : ''; ?>" /></td></tr>
                <tr><th>Type</th><td>
                    <select name="type">
                        <option value="one-time" <?php selected($edit_record ? $edit_record->type : '', 'one-time'); ?>>One-time</option>
                        <option value="recurring" <?php selected($edit_record ? $edit_record->type : '', 'recurring'); ?>>Recurring</option>
                        <option value="seasonal" <?php selected($edit_record ? $edit_record->type : '', 'seasonal'); ?>>Seasonal</option>
                    </select>
                </td></tr>
                <tr><th>Email</th><td><input name="email" type="email" value="<?php echo $edit_record ? esc_attr($edit_record->email) : ''; ?>" required /></td></tr>
                <tr><th>Hours</th><td><input name="hours" type="number" value="<?php echo $edit_record ? esc_attr($edit_record->hours) : ''; ?>" /></td></tr>
                <tr><th>Description</th><td><textarea name="description"><?php echo $edit_record ? esc_textarea($edit_record->description) : ''; ?></textarea></td></tr>
                <tr><th>Location</th><td><input name="location" type="text" value="<?php echo $edit_record ? esc_attr($edit_record->location) : ''; ?>" /></td></tr>
                <tr><th>Skills</th><td><textarea name="skills"><?php echo $edit_record ? esc_textarea($edit_record->skills) : ''; ?></textarea></td></tr>
            </table>
            <input type="submit" name="save_volunteer" class="button button-primary" value="<?php echo $edit_record ? 'Update Opportunity' : 'Save Opportunity'; ?>">
            <?php if ($edit_record) : ?>
                <a href="<?php echo admin_url('admin.php?page=volunteer-plugin'); ?>" class="button">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>
    <?php     
}
// added shortcode listener
add_shortcode('volunteer', 'vol_plugin_shortcode_handler');

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
    $output = '<style>
        .vol-table { width:100%; border-collapse: collapse; margin: 20px 0; font-family: sans-serif; }
        .vol-table th, .vol-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .vol-table th { background-color: #f4f4f4; }
        .vol-table tr:nth-child(even) { background-color: #f9f9f9; }
    </style>';
    // Generate HTML table
    $output .= '<table class="vol-table" style="border-collapse: collapse;">';
    $output .= '<tr><th>Position</th><th>Org</th><th>Type</th><th>Hours</th><th>Email</th><th>Location</th><th>Skills</th><th>Description</th></tr>';
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
        $output .= "<td><em><strong>" . esc_html($row->position) . "</strong></em></td>";
        $output .= "<td>" . esc_html($row->organization) . "</td>";
        $output .= "<td>" . esc_html($row->type) . "</td>";
        $output .= "<td>" . esc_html($row->hours) . "</td>";
        $output .= "<td>" . esc_html($row->email) . "</td>";
        $output .= "<td>" . esc_html($row->location) . "</td>";
        $output .= "<td>" . nl2br(esc_html($row->skills)) . "</td>";
        $output .= "<td>" . nl2br(esc_html($row->description)) . "</td>";
        $output .= "</tr>";
    }
    $output .= '</table>';
    // Return the generated table
    return $output;
}

register_deactivation_hook(__FILE__, 'vol_plugin_deactivate');
function vol_plugin_deactivate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'volunteer_opportunities';
    // Clear all data on deactivation
    $wpdb->query("TRUNCATE TABLE $table_name");
}

// Temporary seeding script
function vol_plugin_seed_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'volunteer_opportunities';

    $records = [
        ['position' => 'Beach Cleanup', 'organization' => 'Ocean Rescue', 'type' => 'one-time', 'email' => 'save@ocean.org', 'hours' => 5, 'description' => 'Help us keep the coastline clean after the weekend crowds.', 'location' => 'Main Street Beach', 'skills' => 'Physical stamina, Environmental awareness'],
        ['position' => 'Weekly Tutor', 'organization' => 'LearnBright', 'type' => 'recurring', 'email' => 'tutor@learn.org', 'hours' => 45, 'description' => 'Help local middle school students with their algebra homework.', 'location' => 'Community Library', 'skills' => 'Mathematics, Patience, Communication'],
        ['position' => 'Crisis Counselor', 'organization' => 'Helpline Inc', 'type' => 'recurring', 'email' => 'help@line.org', 'hours' => 120, 'description' => 'Provide support to individuals in distress via our 24/7 hotline.', 'location' => 'Remote / Office Center', 'skills' => 'Active listening, Empathy, Crisis management'],
        ['position' => 'Park Guide', 'organization' => 'City Parks', 'type' => 'seasonal', 'email' => 'info@parks.gov', 'hours' => 15, 'description' => 'Lead walking tours through the botanical gardens during the summer bloom.', 'location' => 'City Botanical Gardens', 'skills' => 'Public speaking, Botany knowledge, Punctuality'],
    ];

    foreach ($records as $record) {
        $wpdb->insert($table_name, $record);
    }
}
// Uncomment the line below, save, and refresh your WP admin to add data:
// Afterwards, comment it back or remove it to avoid duplicate entries.
//add_action('admin_init', 'vol_plugin_seed_data');