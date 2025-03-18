<?php
// includes/admin.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPForms_Mpesa_Admin {

    public function __construct() {
        add_filter( 'wpforms_entry_columns', [ $this, 'add_mpesa_columns' ] );
        add_action( 'wpforms_entry_column_mpesa_status', [ $this, 'display_mpesa_status' ], 10, 2 );
        add_action( 'wpforms_entry_column_mpesa_transaction', [ $this, 'display_mpesa_transaction' ], 10, 2 );
    }

    /**
     * Add custom M-Pesa columns to WPForms Entries List
     */
    public function add_mpesa_columns( $columns ) {
        $columns['mpesa_status'] = __( 'M-Pesa Status', 'wpforms-mpesa' );
        $columns['mpesa_transaction'] = __( 'Transaction ID', 'wpforms-mpesa' );
        return $columns;
    }

    /**
     * Display the M-Pesa payment status in the entries list
     */
    public function display_mpesa_status( $entry_id, $form_data ) {
        $status = get_post_meta( $entry_id, 'mpesa_payment_status', true ) ?: 'pending';

        // Apply color coding based on status
        switch ( strtolower( $status ) ) {
            case 'completed':
                $color = 'green';
                break;
            case 'failed':
                $color = 'red';
                break;
            default:
                $color = 'gray';
        }

        echo '<span style="color: ' . esc_attr( $color ) . '; font-weight: bold;">' . esc_html( ucfirst( $status ) ) . '</span>';
    }

    /**
     * Display the M-Pesa transaction ID in the entries list
     */
    public function display_mpesa_transaction( $entry_id, $form_data ) {
        $transaction_id = get_post_meta( $entry_id, 'mpesa_transaction_id', true );

        if ( ! empty( $transaction_id ) ) {
            // Make transaction ID clickable if there's a query page
            $query_url = 'https://safaricom.co.ke/mpesa-transaction-check?txid=' . urlencode( $transaction_id );
            echo '<a href="' . esc_url( $query_url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>';
        } else {
            echo '<span style="color: gray;">N/A</span>';
        }
    }
}

new WPForms_Mpesa_Admin();
