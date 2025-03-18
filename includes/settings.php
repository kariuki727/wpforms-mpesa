<?php
// includes/settings.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPForms_Mpesa_Settings {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
    }

    public function add_settings_page() {
        add_options_page(
            'WPForms M-Pesa Settings',
            'WPForms M-Pesa',
            'manage_options',
            'wpforms-mpesa-settings',
            [ $this, 'settings_page_html' ]
        );
    }

    public function register_settings() {
        $options = [
            'wpforms_mpesa_consumer_key',
            'wpforms_mpesa_consumer_secret',
            'wpforms_mpesa_shortcode',
            'wpforms_mpesa_passkey',
            'wpforms_mpesa_environment',
        ];

        foreach ( $options as $option ) {
            register_setting( 'wpforms_mpesa_settings', $option, [ $this, 'sanitize_input' ] );
        }
    }

    public function sanitize_input( $input ) {
        return sanitize_text_field( $input );
    }

    public function get_auto_callback_url() {
        return home_url( '/wp-json/wpforms-mpesa/v1/callback/' );
    }

    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $callback_url = esc_url( get_option( 'wpforms_mpesa_callback_url', $this->get_auto_callback_url() ) );
        ?>
        <div class="wrap wpforms-mpesa-settings">
            <h1>WPForms M-Pesa Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'wpforms_mpesa_settings' );
                do_settings_sections( 'wpforms_mpesa_settings' );
                ?>
                <table class="form-table">
                    <tr>
                        <th><label for="wpforms_mpesa_consumer_key">Consumer Key</label></th>
                        <td><input type="text" id="wpforms_mpesa_consumer_key" name="wpforms_mpesa_consumer_key" value="<?php echo esc_attr( get_option('wpforms_mpesa_consumer_key') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="wpforms_mpesa_consumer_secret">Consumer Secret</label></th>
                        <td><input type="text" id="wpforms_mpesa_consumer_secret" name="wpforms_mpesa_consumer_secret" value="<?php echo esc_attr( get_option('wpforms_mpesa_consumer_secret') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="wpforms_mpesa_shortcode">Shortcode</label></th>
                        <td><input type="text" id="wpforms_mpesa_shortcode" name="wpforms_mpesa_shortcode" value="<?php echo esc_attr( get_option('wpforms_mpesa_shortcode') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="wpforms_mpesa_passkey">Passkey</label></th>
                        <td><input type="text" id="wpforms_mpesa_passkey" name="wpforms_mpesa_passkey" value="<?php echo esc_attr( get_option('wpforms_mpesa_passkey') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>Callback URL</label></th>
                        <td>
                            <input type="text" id="wpforms_mpesa_callback_url" name="wpforms_mpesa_callback_url" value="<?php echo esc_attr( $callback_url ); ?>" class="regular-text" readonly>
                            <p class="description">This is automatically generated. No need to change it.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpforms_mpesa_environment">Environment</label></th>
                        <td>
                            <select id="wpforms_mpesa_environment" name="wpforms_mpesa_environment">
                                <option value="sandbox" <?php selected( get_option('wpforms_mpesa_environment'), 'sandbox' ); ?>>Sandbox</option>
                                <option value="live" <?php selected( get_option('wpforms_mpesa_environment'), 'live' ); ?>>Live</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_admin_styles( $hook ) {
        if ( $hook === 'settings_page_wpforms-mpesa-settings' ) {
            wp_enqueue_style( 'wpforms-mpesa-admin-style', plugin_dir_url( __FILE__ ) . '../assets/admin-style.css' );
        }
    }
}

new WPForms_Mpesa_Settings();
