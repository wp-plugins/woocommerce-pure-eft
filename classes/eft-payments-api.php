<?php

/**
* Pure EFT Payment Gateway
*
* @class      WC_Gateway_Pure_EFT
* @extends    WC_Payment_Gateway
* @version    1.0
* @author     Jason Raveling
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_Pure_EFT extends WC_Payment_Gateway {
    
    /**
    * Constructor for the gateway.
    */
    public function __construct() {
        $this->id                 = 'pure-eft';
        $this->icon               = apply_filters('woocommerce_gateway_icon', 'pure-eft');
        $this->has_fields         = true;
        $this->method_title       = __( 'Pure EFT', 'pure-eft' );
        $this->method_description = __( 'Adds ability to accept checking account information for manual Electronic Funds Transfers (ETF). Right now, this plugin <strong>only collects the information</strong> and sends it via email.', 'pure-eft' );

		// Load the fields
		$this->init_form_fields();
        
        // Load the settings
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
        
        // Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
    * Initialise Gateway Settings Form Fields
    */
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'pure-eft' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable EFT Payment', 'pure-eft' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'pure-eft' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'pure-eft' ),
				'default'     => __( 'Electronic Funds Transfer (EFT)', 'pure-eft' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'pure-eft' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'pure-eft' ),
				'default'     => __( 'Please enter your checking account and routing number.', 'pure-eft' ),
				'desc_tip'    => true,
			)
        );
    }
    
    public function get_plugin_url() {
        return str_replace('/classes','',untrailingslashit( plugins_url( '/', __FILE__ ) ) );
    }
    
    /* Get the icon. Check if SSL is enabled and provide URL */
    public function get_icon() {
        global $woocommerce;
        
        if ( get_option('woocommerce_force_ssl_checkout')=='no' ) {
            $icon = '<img src="' . esc_url( $this->get_plugin_url() . '/assets/icon.png' ) . '" alt="Electronic Funds Transfer (EFT)" />';
        } else {
            $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url( $this->get_plugin_url() ) . '/assets/icon.png' ) . '" alt="Electronic Funds Transfer (EFT)" />';
        }
        
        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }
    
    /**
    * Bank Account Fields
    * 
    * The form for account and routing numbers
    */
    public function pure_eft_account_form() {
        $fields = array(
            'account-number-field' => '<p class="form-row form-row-first">
            <label for="' . $this->id . '-account-number">' . __( "Account Number", 'pure-eft' ) . ' <span class="required">*</span></label>
            <input id="' . $this->id . '-account-number" class="input-text pure-eft-form-account-number" type="text" maxlength="25" autocomplete="off" placeholder="112233456789" name="' . $this->id . '-account-number" />
            </p>',
            'routing-number-field' => '<p class="form-row form-row-first">
            <label for="' . $this->id . '-routing-number">' . __( "Routing Number", 'pure-eft' ) . ' <span class="required">*</span></label>
            <input id="' . $this->id . '-routing-number" class="input-text pure-eft-form-routing-number" type="text" maxlength="9" autocomplete="off" placeholder="123456789" name="' . $this->id . '-routing-number" />
            </p>',
            'account-type-field' => '<p class="form-row form-row-wide">
            <label for "' . $this->id . '-account-type">' . __( "Account Type", 'pure-eft' ). ' <span class="required">*</span></label>
            <input id="' . $this->id . '-account-type" class="pure-eft-form-account-type" type="radio" name="' . $this->id . '-account-type" value="Checking" /> Checking<br />
            <input id="' . $this->id . '-account-type" class="pure-eft-form-account-type" type="radio" name="' . $this->id . '-account-type" value="Savings" /> Savings
            </p>'
        ); ?>
        <fieldset id="<?php echo $this->id; ?>-acct-form">
            <?php
            echo $fields['routing-number-field'];
            echo $fields['account-number-field'];
            echo $fields['account-type-field'];
            ?>
            <div class="clear"></div>
        </fieldset>
    <?php }
    
    /**
    * Payment form on the checkout page
    */
    public function payment_fields() {
		global $woocommerce;
		if ( $this->description ) echo $this->description . "<br /><sup>Please enter only numbers (no spaces or dashes)</sup>";
    
        // Print the customer input form
        $this->pure_eft_account_form();
        
        // Instructions for finding routing and account number
        if ( get_option('woocommerce_force_ssl_checkout')=='no' ) {
            echo '<img src="' . esc_url( $this->get_plugin_url() . '/assets/check-instructions.png' ) . '" alt="Example of numbers on check (Account, Routing, Check number)" />';
        } else {
            echo '<img src="' . esc_url( WC_HTTPS::force_https_url( $this->get_plugin_url() ) . '/assets/check-instructions.png' ) . '" alt="Example of numbers on check (Account, Routing, Check number)" />';
        }                            
    }

    /**
    * Validate user input from the payment form
    */
    public function validate_fields() {
        global $woocommerce;
        
        // Get the input
        $account_number = isset($_POST[$this->id . '-account-number']) ? wc_clean($_POST[$this->id . '-account-number']) : '';
        $routing_number = isset($_POST[$this->id . '-routing-number']) ? wc_clean($_POST[$this->id . '-routing-number']) : '';
        $account_type = isset($_POST[$this->id . '-account-type']) ? wc_clean($_POST[$this->id . '-account-type']) : '';
        $account_type = strval($account_type);
        
        try {
            // Validate $routing_number
            if ( empty( $routing_number ) ) {
                throw new Exception( __( 'Routing number must be provided.', 'pure-eft' ) );
            } elseif ( !eregi( "^[0-9]+$", $routing_number ) ) {
                throw new Exception( __( 'Routing number must be numbers only.', 'pure-eft' ) );
            } elseif ( strlen( $routing_number ) != 9 ) {
                throw new Exception( __( 'Routing number is invalid (It must be 9 digits)', 'pure-eft' ) );
            }
            
            // Validate $account_number
            if ( empty( $account_number ) ) {
                throw new Exception( __( 'Account number must be provided.', 'pure-eft' ) );    
            } elseif (!eregi( "^[0-9]+$", $account_number ) ) {
                throw new Exception( __( 'Account number must be numbers only.', 'pure-eft' ) );
            }
            
            if ( empty( $account_type ) ) {
                throw new Exception( __( 'Please select an account type.', 'pure-eft' ) );
            }
            return true;
            
			} catch( Exception $e ) {
                if ( function_exists( 'wc_add_notice' ) ) {
                    wc_add_notice( $e->getMessage(), 'error' );
                } else {
					$message = ( $e->getMessage() );
						wc_add_notice( $message, 'error' );
				}
				return false;
			}
    }
    
    /**
    * Process the payment and return the result
    *
    * @param int $order_id
    * @return array
    */
    public function process_payment( $order_id ) {
        global $woocommerce, $account_number, $routing_number, $account_type;
        
        $order = wc_get_order( $order_id );
        
        $account_number = isset($_POST[$this->id . '-account-number']) ? wc_clean($_POST[$this->id . '-account-number']) : '';
        $routing_number = isset($_POST[$this->id . '-routing-number']) ? wc_clean($_POST[$this->id . '-routing-number']) : '';
        $account_type = isset($_POST[$this->id . '-account-type']) ? wc_clean($_POST[$this->id . '-account-type']) : '';
        
        add_action( 'woocommerce_email_after_order_table', 'add_account_info', 10, 2 );
        function add_account_info( $order, $is_admin_email ) {
            global $woocommerce, $account_number, $routing_number, $account_type;
            
            if ( $is_admin_email ) {
                echo '<h4>Account: ' . $account_number . '</h4>';
                echo '<h4>Routing: ' . $routing_number . '</h4>';
            } else {
                echo '<h4>Account: XXXXX' . substr( $account_number, -4 ) . '</h4>';
            }
            
            echo '<h4>Type: ' . $account_type . '</h4>';
        }
        
        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status( 'processing', __( 'Awaiting EFT payment', 'pure-eft' ) );
        
        // Reduce stock levels
        $order->reduce_order_stock();
        
        // Add the account info to the order
        $order->add_order_note( __("Acct: {$account_number}; Routing: {$routing_number}; Type: {$account_type}", 'pure-eft') );
        
        // Remove cart
        WC()->cart->empty_cart();
        
        // Return thankyou redirect
        return array(
            'result' 	=> 'success',
            'redirect'	=> $this->get_return_url( $order )
        );
    }
}
