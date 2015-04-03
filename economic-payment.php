<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * e-conomic Invoice Payment Gateway
 *
 * Provides a e-conomic Invoice Payment Gateway.
 *
 * @class 		WC_Economic_Invoice
 * @extends		WC_Payment_Gateway
 * @version		1.0
 * @author 		WooBill
 */
add_action( 'plugins_loaded', 'init_economic_payment' );
function init_economic_payment() {
	class WC_Economic_Invoice extends WC_Payment_Gateway {
		function __construct(){
			$this->id = 'economic-invoice';
			$this->icon	= '';
			$this->has_fields = false;
			$this->method_title = "e-conomic Invoice";
			$this->method_description = "Receive an invoice from e-conomic in no time! An administrative fee of 10SEK will be charged.";
			
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
	
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_economic-invoice', array( $this, 'thankyou_page_economic' ) );
	
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions_economic' ), 10, 3 );
		}
		
		/**
		 * Initialise Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable e-conomic Invoice Payment', 'woocommerce' ),
					'label'   => __( 'Enable e-conomic Invoice Payment', 'woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( 'e-conomic Invoice', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => __( 'Receive an invoice from e-conomic in no time! An Administrative fee of 10SEK will be charged.', 'woocommerce' ), //to do make the admin fee a dynamic variable.
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
					'default'     => 'An e-conomic Invoice will be send from e-conomic, please follow the instructions there!',
					'desc_tip'    => true,
				),
			);
		}
		
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page_economic() {
			if ( $this->instructions )
				echo wpautop( wptexturize( $this->instructions ) );
		}
		
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions_economic( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && 'economic-invoice' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
		
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
	
			// Mark as on-hold (we're awaiting the cheque)
			$order->update_status( 'on-hold', __( 'Awaiting e-conomic Invoice payment', 'woocommerce' ) );
	
			// Reduce stock levels
			$order->reduce_order_stock();
	
			// Remove cart
			WC()->cart->empty_cart();
	
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}
	}
}

function economic_payment_class( $methods ) {
	$methods[] = 'WC_Economic_Invoice'; 
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'economic_payment_class' );
?>