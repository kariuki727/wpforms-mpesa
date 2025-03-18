<?php
// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// List of options to remove.
$mpesa_options = [
    'wpforms_mpesa_consumer_key',
    'wpforms_mpesa_consumer_secret',
    'wpforms_mpesa_shortcode',
    'wpforms_mpesa_passkey',
    'wpforms_mpesa_callback_url',
    'wpforms_mpesa_environment',
];

// Delete each option from the WordPress database.
foreach ( $mpesa_options as $option ) {
    $value = get_option( $option );
    if ( $value !== false ) {
        delete_option( $option );
    }
}

// If using a custom database table for transactions, remove it (optional).
global $wpdb;
$table_name = $wpdb->prefix . 'wpforms_mpesa_transactions';

// Check if table exists before dropping it.
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}
