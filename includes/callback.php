<?php
// includes/callback.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPForms_Mpesa_Callback_Handler {

    private $secret_key;

    public function __construct() {
        $this->secret_key = get_option( 'wpforms_mpesa_secret_key', 'your-secret-key' ); // Set a secret key
        add_action( 'rest_api_init', [ $this, 'register_callback_endpoint' ] );
    }

    public function register_callback_endpoint() {
        register_rest_route( 'wpforms-mpesa/v1', '/callback/', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_callback' ],
            'permission_callback' => '__return_true', // We'll validate manually
        ]);
    }

    public function handle_callback( WP_REST_Request $request ) {
        // Check Authorization Header
        $auth_header = $request->get_header( 'Authorization' );
        if ( $auth_header !== 'Bearer ' . $this->secret_key ) {
            error_log( 'M-Pesa Callback: Unauthorized access attempt.' );
            return new WP_REST_Response( [ 'message' => 'Unauthorized' ], 403 );
        }

        $data = $request->get_json_params();
        error_log( 'M-Pesa Callback Received: ' . json_encode( $data ) );

        if ( empty( $data ) || ! isset( $data['Body']['stkCallback'] ) ) {
            return new WP_REST_Response( [ 'message' => 'Invalid request' ], 400 );
        }

        $callback       = $data['Body']['stkCallback'];
        $result_code    = $callback['ResultCode'] ?? '';
        $checkout_id    = $callback['CheckoutRequestID'] ?? '';
        $result_desc    = $callback['ResultDesc'] ?? '';
        $amount         = 0;
        $phone_number   = '';
        $transaction_id = '';

        // Extract metadata safely
        if ( $result_code == 0 && isset( $callback['CallbackMetadata']['Item'] ) ) {
            foreach ( $callback['CallbackMetadata']['Item'] as $item ) {
                if ( isset( $item['Name'], $item['Value'] ) ) {
                    switch ( $item['Name'] ) {
                        case 'Amount':
                            $amount = $item['Value'];
                            break;
                        case 'MpesaReceiptNumber':
                            $transaction_id = $item['Value'];
                            break;
                        case 'PhoneNumber':
                            $phone_number = $item['Value'];
                            break;
                    }
                }
            }
        }

        // Log transaction for debugging
        error_log( "M-Pesa Payment Processed - CheckoutID: $checkout_id | Result: $result_code | Amount: $amount | Phone: $phone_number | Transaction ID: $transaction_id" );

        // Find WPForms entry with this checkout ID
        $entries = get_posts( [
            'post_type'   => 'wpforms_entries',
            'meta_query'  => [
                [
                    'key'   => 'mpesa_checkout_request_id',
                    'value' => $checkout_id,
                ],
            ],
        ]);

        if ( empty( $entries ) ) {
            error_log( "M-Pesa Callback Error: Entry not found for Checkout ID $checkout_id" );
            return new WP_REST_Response( [ 'message' => 'Entry not found' ], 404 );
        }

        $entry_id = $entries[0]->ID;

        // Update WPForms entry with payment details
        update_post_meta( $entry_id, 'mpesa_payment_status', $result_code == 0 ? 'Completed' : 'Failed' );
        update_post_meta( $entry_id, 'mpesa_transaction_id', $transaction_id );
        update_post_meta( $entry_id, 'mpesa_phone_number', $phone_number );
        update_post_meta( $entry_id, 'mpesa_payment_amount', $amount );
        update_post_meta( $entry_id, 'mpesa_result_description', $result_desc );

        return new WP_REST_Response( [ 'message' => 'Callback processed' ], 200 );
    }
}

new WPForms_Mpesa_Callback_Handler();
