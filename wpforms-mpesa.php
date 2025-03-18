<?php
/**
 * Plugin Name: WPForms M-Pesa Gateway
 * Plugin URI:  https://briceka.com/wpforms-mpesa-plugin
 * Description: Adds M-Pesa as a payment gateway in WPForms.
 * Version:     1.0
 * Author:      KVO
 * Author URI:  https://briceka.com/author/kvo
 * License:     GPL2
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin path
define( 'WPFORMS_MPESA_PATH', plugin_dir_path( __FILE__ ) );

// Load necessary files
require_once WPFORMS_MPESA_PATH . 'includes/class-mpesa-field.php';
require_once WPFORMS_MPESA_PATH . 'includes/settings.php';
require_once WPFORMS_MPESA_PATH . 'includes/mpesa-api.php';
require_once WPFORMS_MPESA_PATH . 'includes/process-payment.php';
require_once WPFORMS_MPESA_PATH . 'includes/callback.php';
require_once WPFORMS_MPESA_PATH . 'includes/admin.php';

// Activation Hook
function wpforms_mpesa_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpforms_mpesa_transactions';

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_code VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        phone_number VARCHAR(15) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook( __FILE__, 'wpforms_mpesa_activate' );

// Deactivation Hook
function wpforms_mpesa_deactivate() {
    // Optional: Remove scheduled events, temporary data, etc.
}
register_deactivation_hook( __FILE__, 'wpforms_mpesa_deactivate' );

// Uninstall Hook (automatically runs when plugin is deleted)
register_uninstall_hook( __FILE__, 'wpforms_mpesa_uninstall' );
function wpforms_mpesa_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpforms_mpesa_transactions';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    delete_option('wpforms_mpesa_settings');
}
