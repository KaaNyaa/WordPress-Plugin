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

