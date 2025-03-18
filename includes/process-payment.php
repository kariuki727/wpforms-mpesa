<?php
// includes/process-payment.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPForms_Mpesa_Process_Payment {

    public function __construct() {
        add_action( 'wpforms_process_complete', [ $this, 'initiate_mpesa_payment' ], 10, 4 );
    }

    public function initiate_mpesa_payment( $fields, $entry, $form_data, $entry_id ) {

        // Get M-Pesa settings
        $consumer_key    = get_option( 'wpforms_mpesa_consumer_key' );
        $consumer_secret = get_option( 'wpforms_mpesa_consumer_secret' );
        $shortcode       = get_option( 'wpforms_mpesa_shortcode' );
        $passkey         = get_option( 'wpforms_mpesa_passkey' );
        $environment     = get_option( 'wpforms_mpesa_environment', 'sandbox' );

        // Auto-generate callback URL
        $callback_url = rest_url( 'wpforms-mpesa/v1/callback/' );

        // Get form payment details
        $phone_number = ''; 
        $amount = 0;

        foreach ( $fields as $field ) {
            if ( strpos( strtolower( $field['name'] ), 'phone' ) !== false ) {
                $phone_number = preg_replace( '/[^0-9]/', '', $field['value'] ); // Remove non-numeric characters
            }
            if ( strpos( strtolower( $field['name'] ), 'amount' ) !== false ) {
                $amount = floatval( $field['value'] );
            }
        }

        if ( empty( $phone_number ) || empty( $amount ) || $amount <= 0 ) {
            return;
        }

        // 📌 Improved Phone Number Formatting (Supports 011, 012, 0100, etc.)
        if ( preg_match( '/^0\d{9}$/', $phone_number ) ) {
            // Convert "0712345678" → "254712345678"
            $phone_number = '254' . substr( $phone_number, 1 );
        } elseif ( preg_match( '/^254\d{9}$/', $phone_number ) ) {
            // Already in correct format
        } else {
            return; // Invalid phone number
        }

        // Get access token
        $token_url = $environment === 'live' 
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' 
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode( "$consumer_key:$consumer_secret" );

        $response = wp_remote_get( $token_url, [
            'headers' => [
                'Authorization' => "Basic $credentials"
            ]
        ]);

        if ( is_wp_error( $response ) ) {
            return;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $access_token = $body['access_token'] ?? '';

        if ( empty( $access_token ) ) {
            return;
        }

        // Process STK Push request
        $timestamp = date( 'YmdHis' );
        $password  = base64_encode( $shortcode . $passkey . $timestamp );

        $stk_url = $environment === 'live' 
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' 
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $stk_data = [
            'BusinessShortCode' => $shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone_number,
            'PartyB'            => $shortcode,
            'PhoneNumber'       => $phone_number,
            'CallBackURL'       => $callback_url,  // Auto-generated
            'AccountReference'  => 'WPForms Payment',
            'TransactionDesc'   => 'Form Payment'
        ];

        $stk_response = wp_remote_post( $stk_url, [
            'headers' => [
                'Authorization' => "Bearer $access_token",
                'Content-Type'  => 'application/json'
            ],
            'body' => json_encode( $stk_data )
        ]);

        if ( is_wp_error( $stk_response ) ) {
            return;
        }

        $stk_body = json_decode( wp_remote_retrieve_body( $stk_response ), true );

        if ( isset( $stk_body['ResponseCode'] ) && $stk_body['ResponseCode'] == "0" ) {
            update_post_meta( $entry_id, 'mpesa_checkout_request_id', $stk_body['CheckoutRequestID'] );
        }
    }
}

new WPForms_Mpesa_Process_Payment();
