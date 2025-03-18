<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WPForms_Field_Mpesa
 * Custom WPForms field for M-Pesa payments.
 */
class WPForms_Field_Mpesa extends WPForms_Field {

    /**
     * Constructor to initialize the field.
     */
    public function __construct() {
        $this->name  = esc_html__( 'M-Pesa Payment', 'wpforms-mpesa' );
        $this->type  = 'mpesa_payment';
        $this->icon  = 'fa fa-money'; // Change if necessary.

        parent::__construct(); // Ensure proper field registration.
    }

    /**
     * Initialize the field.
     */
    public function init() {
        parent::init();
    }

    /**
     * Field options in WPForms builder.
     */
    public function field_options( $field ) {
        // Parent field options (like Label, Description, Required, etc.).
        $this->field_option( 'basic-options', $field, [] );
    }

    /**
     * Output the field in the frontend.
     */
    public function field_display( $field, $field_atts, $form_data ) {
        $field_id  = esc_attr( $field['id'] );
        $field_val = ! empty( $_POST['wpforms']['fields'][ $field_id ] ) ? esc_attr( $_POST['wpforms']['fields'][ $field_id ] ) : '';

        // Input field for M-Pesa transaction code
        echo '<input type="text" name="wpforms[fields][' . $field_id . ']" value="' . $field_val . '" class="wpforms-mpesa-input" placeholder="Enter M-Pesa Transaction Code">';
    }
}

// Register the custom field
function wpforms_register_mpesa_field( $fields ) {
    $fields['mpesa_payment'] = 'WPForms_Field_Mpesa';
    return $fields;
}
add_filter( 'wpforms_fields_register', 'wpforms_register_mpesa_field' );
