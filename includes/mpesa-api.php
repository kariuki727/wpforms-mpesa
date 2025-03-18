<?php
// includes/mpesa-api.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPForms_Mpesa_API {
    private $consumer_key;
    private $consumer_secret;
    private $shortcode;
    private $passkey;
    private $callback_url;
    private $env;

    public function __construct() {
        $this->consumer_key    = get_option( 'wpforms_mpesa_consumer_key' );
        $this->consumer_secret = get_option( 'wpforms_mpesa_consumer_secret' );
        $this->shortcode       = get_option( 'wpforms_mpesa_shortcode' );
        $this->passkey         = get_option( 'wpforms_mpesa_passkey' );
        $this->env             = get_option( 'wpforms_mpesa_environment', 'sandbox' );

        // ✅ Auto-generate callback URL dynamically
        $this->callback_url = rest_url('wpforms-mpesa/v1/callback/');
    }

    private function get_api_url( $endpoint ) {
        $base_url = ( $this->env === 'live' ) ? 'https://api.safaricom.co.ke/' : 'https://sandbox.safaricom.co.ke/';
        return $base_url . $endpoint;
    }

    public function get_access_token() {
        $url = $this->get_api_url('oauth/v1/generate?grant_type=client_credentials');
        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( $this->consumer_key . ':' . $this->consumer_secret ),
                'Content-Type'  => 'application/json',
            ],
        ]);

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['access_token'] ?? false;
    }

    /**
     * ✅ Validate and format the phone number before sending to M-Pesa
     */
    private function format_phone_number( $phone ) {
        // Remove spaces and non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Handle phone numbers starting with '07' or '011'
        if ( preg_match('/^07\d{8}$/', $phone) ) {
            return '254' . substr($phone, 1); // Convert 07XXXXXXXX -> 2547XXXXXXXX
        } elseif ( preg_match('/^01\d{8}$/', $phone) ) {
            return '254' . $phone; // Convert 011XXXXXXXX -> 25411XXXXXXXX
        } elseif ( preg_match('/^254\d{9}$/', $phone) ) {
            return $phone; // Already in correct format
        }

        return false; // Invalid number
    }

    public function stk_push( $phone, $amount, $order_id ) {
        $access_token = $this->get_access_token();
        if ( ! $access_token ) {
            return [ 'error' => 'Failed to authenticate with M-Pesa API' ];
        }

        // ✅ Validate & format phone number
        $phone = $this->format_phone_number($phone);
        if ( ! $phone ) {
            return [ 'error' => 'Invalid phone number format. Please use a valid Safaricom number.' ];
        }

        $timestamp = date('YmdHis');
        $password  = base64_encode( $this->shortcode . $this->passkey . $timestamp );

        $data = [
            'BusinessShortCode' => $this->shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone,
            'PartyB'            => $this->shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->callback_url,  // ✅ Auto-generated callback URL
            'AccountReference'  => 'WPForms Payment',
            'TransactionDesc'   => 'Payment for order ' . $order_id,
        ];

        $url = $this->get_api_url('mpesa/stkpush/v1/processrequest');
        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode( $data ),
        ]);

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // ✅ Better error handling
        if ( isset( $body['ResponseCode'] ) && $body['ResponseCode'] == '0' ) {
            return $body; // Successful request
        } else {
            return [ 'error' => $body['errorMessage'] ?? 'Failed to process payment' ];
        }
    }
}

$mpesa_api = new WPForms_Mpesa_API();
