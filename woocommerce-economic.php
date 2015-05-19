<?php
/**
 * Plugin Name: WooCommerce e-conomic Integration
 * Plugin URI: http://plugins.svn.wordpress.org/woocommerce-economic-integration/
 * Description: An e-conomic API Interface. Synchronizes products, orders, Customers and more to e-conomic.
 * Also fetches inventory from e-conomic and updates WooCommerce
 * Version: 1.2
 * Author: wooconomics
 * Text Domain: woocommerce-e-conomic-integration
 * Author URI: www.wooconomics.com
 * License: GPL2
 */
 if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined('TESTING')){
    define('TESTING',true);
}

if(!defined('AUTOMATED_TESTING')){
    define('AUTOMATED_TESTING', true);
}

if ( ! function_exists( 'logthis' ) ) {
    function logthis($msg) {
        if(TESTING){
            if(!file_exists(dirname(__FILE__).'/logfile.log')){
                $fileobject = fopen(dirname(__FILE__).'/logfile.log', 'a');
                chmod(dirname(__FILE__).'/logfile.log', 0666);
            }
            else{
                $fileobject = fopen(dirname(__FILE__).'/logfile.log', 'a');
            }

            if(is_array($msg) || is_object($msg)){
                fwrite($fileobject,print_r($msg, true));
            }
            else{
                fwrite($fileobject,date("Y-m-d H:i:s"). ":" . $msg . "\n");
            }
        }
        else{
            error_log($msg);
        }
    }
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    if ( ! class_exists( 'WCEconomic' ) ) {
		
		
		//Add e-conomic payment class
		include_once("economic-payment.php");

        // in javascript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
        function economic_enqueue(){
            wp_enqueue_script('jquery');
            wp_register_script( 'economic-script', plugins_url( '/js/economic.js', __FILE__ ) );
            wp_enqueue_script( 'economic-script' );
        }

        add_action( 'admin_enqueue_scripts', 'economic_enqueue' );
        add_action( 'wp_ajax_sync_products', 'economic_sync_products_callback' );

        function economic_sync_products_callback() {
			//echo json_encode(array('status' => 'test', 'msg'=>'testing ajax')); exit; die();
            global $wpdb; // this is how you get access to the database
			include_once("class-economic-api.php");
            $wce_api = new WCE_API();
			$wce = new WC_Economic();
			if($wce->is_license_key_valid() != "Active" || !$wce_api->create_API_validation_request()){
			  logthis("economic_sync_products_callback exiting because license key validation not passed.");
			  return false;
			}			
			$log_msg = '';		
			$sync_log = $wce_api->sync_products();
			foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'woocommerce-e-conomic-integration'). $value['status'].'<br>';
				$log_msg .= __('Product SKU: ', 'woocommerce-e-conomic-integration'). $value['sku'].'<br>';
				$log_msg .= __('Product Name: ', 'woocommerce-e-conomic-integration'). $value['name'].'<br>';
				$log_msg .= __('Sync message: ', 'woocommerce-e-conomic-integration'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Products are synchronized without problems.', 'woocommerce-e-conomic-integration'), 'msg' => $log_msg);
				//logthis(json_encode($log));
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'woocommerce-e-conomic-integration'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }

        add_action( 'wp_ajax_sync_orders', 'economic_sync_orders_callback' );
        function economic_sync_orders_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-economic-api.php");
            $wce_api = new WCE_API();
			$wce = new WC_Economic();
			if($wce->is_license_key_valid() != "Active" || !$wce_api->create_API_validation_request()){
			  logthis("economic_sync_products_callback existing because licensen key validation not passed.");
			  return false;
			}
			$log_msg = '';
			$sync_log = $wce_api->sync_orders();
            foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'woocommerce-e-conomic-integration'). $value['status'].'<br>';
				isset($value['order_id']) ? $log_msg .= 'Order ID: '. $value['order_id'].'<br>' : '';
				$log_msg .= __('Sync message: ', 'woocommerce-e-conomic-integration'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Orders are synchronized without problems.', 'woocommerce-e-conomic-integration'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'woocommerce-e-conomic-integration'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }

        add_action( 'wp_ajax_sync_contacts', 'economic_sync_contacts_callback' );
        function economic_sync_contacts_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-economic-api.php");
            $wce_api = new WCE_API();
			$wce = new WC_Economic();
			if($wce->is_license_key_valid() != "Active" || !$wce_api->create_API_validation_request()){
			  logthis("economic_sync_products_callback existing because licensen key validation not passed.");
			  return false;
			}
			$log_msg = '';
			$sync_log = $wce_api->sync_contacts();
            foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'woocommerce-e-conomic-integration'). $value['status'].'<br>';
				isset($value['user_id']) ? $log_msg .= 'Contact ID: '. $value['user_id'].'<br>' : '';
				$log_msg .= __('Sync message: ', 'woocommerce-e-conomic-integration'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Contacts synchronized without problems.', 'woocommerce-e-conomic-integration'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'woocommerce-e-conomic-integration'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }
		
		
		add_action( 'wp_ajax_sync_shippings', 'economic_sync_shippings_callback' );
        function economic_sync_shippings_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-economic-api.php");
            $wce_api = new WCE_API();
			$wce = new WC_Economic();
			if($wce->is_license_key_valid() != "Active" || !$wce_api->create_API_validation_request()){
			  logthis("economic_sync_products_callback existing because licensen key validation not passed.");
			  return false;
			}
			$log_msg = '';
			$sync_log = $wce_api->sync_shippings();
            foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'woocommerce-e-conomic-integration'). $value['status'].'<br>';
				$log_msg .= __('Shipping type: ', 'woocommerce-e-conomic-integration'). $value['name'].'<br>';
				$log_msg .= __('Sync message: ', 'woocommerce-e-conomic-integration'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Delivery synchronized without problems.', 'woocommerce-e-conomic-integration'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'woocommerce-e-conomic-integration'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }

        add_action( 'wp_ajax_send_support_mail', 'economic_send_support_mail_callback' );
		

        function economic_send_support_mail_callback() {

            //$message = 'Kontakta ' . $_POST['name'] . ' <br>på ' . $_POST['company'] . ' <br>antingen på ' .$_POST['telephone'] .' <br>eller ' . $_POST['email'] . ' <br>gällande: <br>' . $_POST['subject'];
			$message = '<html><body><table rules="all" style="border-color: #91B9F6; width:70%; font-family:Calibri, Arial, sans-serif;" cellpadding="10">';
			if(isset($_POST['supportForm']) && $_POST['supportForm'] ==  "support"){
				$message .= '<tr><td align="right">Type: </td><td align="left" colspan="1"><strong>Support</strong></td></tr>';
			}else{
				$message .= '<tr><td align="right">Type: </td><td align="left" colspan="1"><strong>Installationssupport</strong></td></tr>';
			}
			$message .= '<tr><td align="right">Företag: </td><td align="left">'.$_POST['company'].'</td></tr>';
			$message .= '<tr><td align="right">Namn: </td><td align="left">'.$_POST['name'].'</td></tr>';
			$message .= '<tr><td align="right">Telefon: </td><td align="left">'.$_POST['telephone'].'</td></tr>';
			$message .= '<tr><td align="right">Email: </td><td align="left">'.$_POST['email'].'</td></tr>';
			$message .= '<tr><td align="right">Ärende: </td><td align="left">'.$_POST['subject'].'</td></tr>';
			
			if(isset($_POST['supportForm']) && $_POST['supportForm'] ==  "support"){
				$options = get_option('woocommerce_economic_general_settings');
				$order_options = get_option('woocommerce_economic_order_settings');
				$message .= '<tr><td align="right" colspan="1"><strong>Allmänna inställningar</strong></td></tr>';
				$message .= '<tr><td align="right">License Nyckel: </td><td align="left">'.$options['license-key'].'</td></tr>';
				$message .= '<tr><td align="right">Token ID: </td><td align="left">'.$options['token'].'</td></tr>';
				$message .= '<tr><td align="right">Private app ID: </td><td align="left">'.$options['appToken'].'</td></tr>';
				//$message .= '<tr><td align="right">Avtalsnr.: </td><td align="left">'.$options['agreementNumber'].'</td></tr>';
				//$message .= '<tr><td align="right">Användar-ID: </td><td align="left">'.$options['username'].'</td></tr>';
				//$message .= '<tr><td align="right">Lösenord: </td><td align="left">'.$options['password'].'</td></tr>';
				//$message .= '<tr><td align="right">e-conomic synk alternativ: </td><td align="left">'.$options['sync-option'].'</td></tr>';
				//$message .= '<tr><td align="right">Aktivera kassaböckerna: </td><td align="left">'.$options['activate-cashbook'].'</td></tr>';
				//$message .= '<tr><td align="right">Kassaböckerna namn: </td><td align="left">'.$options['cashbook-name'].'</td></tr>';
				$message .= '<tr><td align="right">Produktgrupp: </td><td align="left">'.$options['product-group'].'</td></tr>';
				$message .= '<tr><td align="right">Produkt prefix: </td><td align="left">'.$options['product-prefix'].'</td></tr>';
				$message .= '<tr><td align="right">Kundgrupp: </td><td align="left">'.$options['customer-group'].'</td></tr>';
				$message .= '<tr><td align="right">Aktivera alla beställningar synkning: </td><td align="left">'.$options['activate-allsync'].'</td></tr>';
				//$message .= '<tr><td align="right">Kund prefix: </td><td align="left">'.$options['customer-prefix'].'</td></tr>';
				//$message .= '<tr><td align="right">Frakt produktnummer: </td><td align="left">'.$options['shipping-product-id'].'</td></tr>';
			}
			
			$message .= '</table></html></body>';
	
			
			$headers = "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=utf-8 \r\n";
			//$headers .= "From:".get_option('admin_email')."\r\n";
			
            echo wp_mail( 'support@onlineforce.net', 'e-conomic Support', $message , $headers) ? "success" : "error";
            //die(); // this is required to return a proper result
        }
		
		
		//Test the connection
		
		function economic_test_connection_callback() {
			include_once("class-economic-api.php");
			$wce = new WC_Economic();
			$wce_api = new WCE_API();
			if( $wce->is_license_key_valid() != "Active" ){
				_e('License Key is Invalid!', 'woocommerce-e-conomic-integration');
				die(); // this is required to return a proper result
			}else{
				$data = $wce_api->create_API_validation_request();
				if( $data ){
					_e('Your integration works fine!', 'woocommerce-e-conomic-integration');
					die(); // this is required to return a proper result
				}else{
					_e('Your e-conomic Token ID and Private app ID are not valid!', 'woocommerce-e-conomic-integration');
					die(); // this is required to return a proper result
				}
			}
			_e('Something went wrong, please try again later!', 'woocommerce-e-conomic-integration');
			die(); // this is required to return a proper result
        }
		
		//Connection testing ends

        add_action( 'wp_ajax_test_connection', 'economic_test_connection_callback' );
		
		
		//License key invalid warning message. todo change the license purchase link
		
		function license_key_invalid() {
			$options = get_option('woocommerce_economic_general_settings');
			$wce = new WC_Economic();
			$key_status = $wce->is_license_key_valid();
			if(!isset($options['license-key']) || $options['license-key'] == '' || $key_status!='Active'){
			?>
                <div class="error">
                    <p><?php echo __('WooCommerce e-conomic Integration: License Key Invalid!', 'woocommerce-e-conomic-integration'); ?> <button type="button button-primary" class="button button-primary" title="" style="margin:5px" onclick="window.open('http://whmcs.onlineforce.net/cart.php?a=add&pid=56&carttpl=flex-web20cart&language=English','_blank');"><?php echo __('Get license Key', 'woocommerce-e-conomic-integration'); ?></button></p>
                </div>
			<?php
			}
		}
		
		add_action( 'admin_notices', 'license_key_invalid' );
		//License key invalid warning message ends.


		//Section for wordpress pointers
		
		function economic_wp_pointer_hide_callback(){
			update_option('economic-tour', false);
		}
		add_action( 'wp_ajax_wp_pointer_hide', 'economic_wp_pointer_hide_callback' );
		
		$economic_tour = get_option('economic-tour');
		
		if(isset($economic_tour) && $economic_tour){
			// Register the pointer styles and scripts
			add_action( 'admin_enqueue_scripts', 'enqueue_scripts' );
			
			// Add pointer javascript
			add_action( 'admin_print_footer_scripts', 'add_pointer_scripts' );
		}
		
		// enqueue javascripts and styles
		function enqueue_scripts()
		{
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );	
		}
		
		// Add the pointer javascript
		function add_pointer_scripts()
		{
			$content = __('<h3>WooCommerce e-conomic Integration</h3>', 'woocommerce-e-conomic-integration');
			$content .= __('<p>You’ve just installed WooCommerce e-conomic Integration by wooconomics. Please use the plugin options page to setup your integration.</p>', 'woocommerce-e-conomic-integration');
		
			?>
			
            <script type="text/javascript">
				jQuery(document).ready( function($) {
					$("#toplevel_page_woocommerce_economic_options").pointer({
						content: '<?php echo $content; ?>',
						position: {
							edge: 'left',
							align: 'center'
						},
						close: function() {
							// what to do after the object is closed
							var data = {
								action: 'wp_pointer_hide'
							};
	
							jQuery.post(ajaxurl, data);
						}
					}).pointer('open');
				});
			</script>
		   
		<?php
		}
		
		//Section for wordpress pointers ends.
		
		
		/***********************************************************************************************************
		* e-conomic FUNCTIONS
		***********************************************************************************************************/
		
		
		//Save product to economic from woocommerce.
		add_action('save_post', 'woo_save_object_to_economic', 2, 2);
		function woo_save_object_to_economic( $post_id, $post) {
		  include_once("class-economic-api.php");
		  $wce = new WC_Economic();
		  $wce_api = new WCE_API();
		  if($wce->is_license_key_valid() != "Active" || !$wce_api->create_API_validation_request()){
			  logthis("woo_save_object_to_economic existing because licensen key validation not passed.");
			  return false;
		  }
		  logthis("woo_save_object_to_economic called by post_id: " . $post_id . " posttype: " . $post->post_type);
		  if ( !$_POST ) return $post_id;
		  if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
		  if( is_int( wp_is_post_autosave( $post_id ) ) ) return;
		  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		  if ($post->post_type != 'product' || $post->post_status != 'publish') return $post_id;
		  logthis("woo_save_object_to_economic calling woo_save_".$post->post_type."_to_economic");
		  do_action('woo_save_'.$post->post_type.'_to_economic', $post_id, $post);
		}
		
		add_action('woo_save_product_to_economic', 'woo_save_product_to_economic', 1,2);
		function woo_save_product_to_economic($post_id, $post) {
		  include_once("class-economic-api.php");
		  $wce = new WC_Economic();
		  $wce_api = new WCE_API();
		  //should be handled for syncing products from economic to woocommerce.
		  /*if ($woo_economic_product_lock) {
			logthis("woo_save_product_to_economic cancel save product, product is locked");
			return;
		  }*/
		  logthis("woo_save_product_to_economic product post id: " . $post_id);
		  $product = new WC_Product($post->ID);
		  $client = $wce_api->woo_economic_client();
		  logthis("saving product: " . $product->get_title() . " id: " . $product->id . " sku: " . $product->sku);
		  $wce_api->save_product_to_economic($product, $client);
		}
		//Save product to economic from woocommerce ends.
		
		
		//Save orders to economic from woocommerce.
		/*
		* Action to create invoice/order/quotation
		*/
		add_action('woocommerce_order_status_completed', 'woo_save_invoice_order_to_economic', 10, 4);
		function woo_save_invoice_order_to_economic($order_id) {
			try {
				global $wpdb;
				if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order_id." AND synced=1;")){
					logthis("woo_save_invoice_to_economic: order_id: ".$order_id." is already synced during the checkout");
					return true;
				}
				include_once("class-economic-api.php");
				$options = get_option('woocommerce_economic_general_settings');
				$wce = new WC_Economic();
				$wce_api = new WCE_API();
				if($wce->is_license_key_valid() != "Active" || !$wce_api->create_API_validation_request()){
					logthis('Exiting on API license failure!');
					if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order_id.";")){
						$wpdb->update ("wce_orders", array('synced' => 0), array('order_id' => $order->id), array('%d'), array('%d'));
					}else{
						$wpdb->insert ("wce_orders", array('order_id' => $order_id, 'synced' => 0), array('%d', '%d'));
					}
					return false;
				}
				logthis("woo_save_invoice_order_to_economic: order_id: ".$order_id);
				$order = new WC_Order($order_id);
				$user = new WP_User($order->user_id);
				if($order->payment_method != 'economic-invoice'){
					if($options['activate-allsync'] != "on"){
						if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id." AND synced=0;")){
							return false;
						}else{
							$wpdb->insert ("wce_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
							return false;
						}
					}
				}else{
					if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id." AND synced=1;")){
						logthis("woo_save_invoice_to_economic: order_id: ".$order_id." an e-conomic payment order already synced during the checkout");
						return true;
					}
				}
				$client = $wce_api->woo_economic_client();
				if($options['sync-order-invoice'] == 'invoice'){
					if($wce_api->save_invoice_to_economic($user, $order, $client, false)){
						logthis("woo_save_invoice_to_economic order: " . $order_id . " is synced with economic");
					}
					else{
						logthis("woo_save_invoice_to_economic order: " . $order_id . " sync failed, please try again after sometime!");
					}
				}else{
					if($wce_api->save_order_to_economic($user, $order, $client, false)){
						logthis("woo_save_order_to_economic order: " . $order_id . " is synced with economic");
					}
					else{
						logthis("woo_save_order_to_economic order: " . $order_id . " sync failed, please try again after sometime!");
					}
				}
				
				/**
				* if create auto debtor payment - create it
				
				$auto_create_debtor = $options['activate-cashbook'];
				if (isset($auto_create_debtor) && $auto_create_debtor == 'on') {
					woo_economic_create_debtor_payment($user, $order);
				}*/
			}catch (Exception $exception) {
				logthis("woocommerce_order_status_completed could not sync: " . $exception->getMessage());
				$this->debug_client($client);
				logthis($exception->getMessage);
				$wpdb->insert ("wce_orders", array('order_id' => $order_id, 'synced' => 0), array('%d', '%d'));
				return false;
			}
		}
		
		/*
		* Action to create invoice/order/quotation
		*
		add_action('woocommerce_order_status_refunded', 'woo_refund_order_to_economic', 10, 4);
		function woo_refund_order_to_economic($order_id) {
			include_once("class-economic-api.php");
			$wce_api = new WCE_API();
			logthis("woo_economic_refund_invoice: order_id: ".$order_id);
			$order = new WC_Order($order_id);
			$user = new WP_User($order->user_id);
			$client = $wce_api->woo_economic_client();
			$wce_api->save_invoice_to_economic($user, $order, $client, $order_id . " refunded", true);
		}*/
		
		//Save orders to economic from woocommerce ends.


		
		//Save customers to economic from woocommerce ends.
		
		/*
		 * Create new customer at economic with minimial required data.
		 */
		add_action('woocommerce_checkout_order_processed', 'woo_save_customer_to_economic');
		function woo_save_customer_to_economic($order_id) {
			try{
				include_once("class-economic-api.php");
				global $wpdb;
				$wpdb->insert ("wce_orders", array('order_id' => $order_id, 'synced' => 0), array('%d', '%d'));
				$options = get_option('woocommerce_economic_general_settings');
				$wce = new WC_Economic();
				$wce_api = new WCE_API();
				$order = new WC_Order($order_id);
				$user = new WP_User($order->user_id);
				if($wce->is_license_key_valid() != "Active" || !$wce_api->create_API_validation_request()){
					if($wpdb->query ("SELECT * FROM wce_customers WHERE user_id=".$user->ID.";")){
						$wpdb->update ("wce_customers", array('synced' => 0), array('user_id' => $user->ID), array('%d'), array('%d'));
					}else{
						$wpdb->insert ("wce_customers", array('user_id' => $user->ID, 'customer_number' => 0, 'synced' => 0), array('%d', '%s', '%d'));
					}
					return false;
				}
				logthis("woo_save_customer_to_economic for user_id: " . $user->ID);
			
				if (woo_is_economic_customer($user)) {
					$client = $wce_api->woo_economic_client();
					if($wce_api->save_customer_to_economic($user, $client)){
						logthis("woo_save_customer_to_economic user: " . $user->ID . " is synced with economic.");
						if($order->payment_method != 'economic-invoice'){
							if($options['activate-allsync'] != "on"){
								if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id." AND synced=0;")){
									return false;
								}else{
									$wpdb->insert ("wce_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
									return false;
								}
							}
						}
						if($options['sync-order-invoice'] == 'invoice'){
							if($wce_api->save_invoice_to_economic($user, $order, $client, false)){
								logthis("woo_save_invoice_to_economic order: " . $order_id . " is synced with economic.");
							}
							else{
								logthis("woo_save_invoice_to_economic order: " . $order_id . " sync failed, please try again after sometime!");
							}
							if($order->payment_method == 'economic-invoice'){
								if($wce_api->send_invoice_economic($user, $order, $client)){
									logthis("woo_save_invoice_to_economic invoice for order: " . $order_id . " is sent to customer.");
								}
							}
						}else{
							if($wce_api->save_order_to_economic($user, $order, $client, false)){
								logthis("woo_save_order_to_economic order: " . $order_id . " is synced with economic.");
							}
							else{
								logthis("woo_save_order_to_economic order: " . $order_id . " sync failed, please try again after sometime!");
							}
						}
					}
					else{
						logthis("woo_save_customer_to_economic user: " . $user->ID . "sync failed, please manual sync after sometime!");
					}
				}
			}catch (Exception $exception) {
				logthis("woo_save_customer_to_economic could not sync user/order: " . $exception->getMessage());
				$this->debug_client($client);
				logthis($exception->getMessage);
				$wpdb->insert ("wce_orders", array('order_id' => $order_id, 'synced' => 0), array('%d', '%d'));
				$wpdb->insert ("wce_customers", array('user_id' => $user->ID, 'customer_number' => 0, 'synced' => 0), array('%d', '%s', '%d'));
				return false;
			}
		}
		
		function woo_is_economic_customer(WP_User $user) {
		  $is_customer = false;
		  foreach ($user->roles as $role) {
			logthis("user role: " . $role);
			if ($role == 'customer') {
			  $is_customer = true;
			  break;
			}
		  }
		  return $is_customer;
		}
		
		/*
		 * Save additional user data to economic
		 */
		add_action('update_user_meta', 'woo_update_user_meta_to_economic', 10, 4);
		function woo_update_user_meta_to_economic($meta_id, $object_id, $meta_key, $_meta_value) {
		  global $wpdb;
		  include_once("class-economic-api.php");
		  $wce = new WC_Economic();
		  $wce_api = new WCE_API();
		  if(in_array($meta_key, $wce_api->user_fields)){
			  logthis("woo_update_user_meta_to_economic: meta_id: ".$meta_id." object_id: ".$object_id." meta_key: ".$meta_key." meta_value: ".$_meta_value);
			  if($wce->is_license_key_valid() != "Active" || !$wce_api->create_API_validation_request()){
				  if($wpdb->query ("SELECT * FROM wce_customers WHERE user_id=".$object_id.";")){
					 $wpdb->update ("wce_customers", array('synced' => 0), array('user_id' => $object_id), array('%d'), array('%d'));
				  }else{
					 $wpdb->insert ("wce_customers", array('user_id' => $object_id, 'customer_number' => 0, 'synced' => 0), array('%d', '%s', '%d'));
				  }
				  return false;
			  }
			  $user = new WP_User($object_id);
			  if (woo_is_economic_customer($user)) {
				$client = $wce_api->woo_economic_client();
				
				$debtorHandle = $wce_api->woo_get_debtor_handle_from_economic($user, $client);
				$debtor_delivery_location_handle = $wce_api->woo_get_debtor_delivery_location_handles_from_economic($user, $client);
				
				if($wce_api->woo_save_customer_meta_data_to_economic($user, $client, $meta_key, $_meta_value, $debtorHandle, $debtor_delivery_location_handle)){
					$wpdb->update ("wce_customers", array('synced' => 1), array('user_id' => $user->ID), array('%d'), array('%d'));
					logthis("woo_update_user_meta_to_economic user: " . $user->ID . " additional data is synced with economic");
				}
				else{
					$wpdb->update ("wce_customers", array('synced' => 0), array('user_id' => $user->ID), array('%d'), array('%d'));
					logthis("woo_update_user_meta_to_economic user: " . $user->ID . " additional data sync failed, please try again after sometime!");
				}
			  }
		  }else{
			  logthis("woo_update_user_meta_to_economic: Not selected for sync, skipping meta_id: ".$meta_id." object_id: ".$object_id);
		  }
		}
		
		//Save customers to economic from woocommerce ends.


		//Section for Plugin installation and activation
		/**
		 * Creates tables for WooCommerce Economic
		 *
		 * @access public
		 * @param void
		 * @return bool
		 */
		function economic_install(){
			add_option('economic-tour', true);
			global $wpdb;
			$wce_orders = "wce_orders";
			$wce_customers = "wce_customers";
			
			$sql = "CREATE TABLE IF NOT EXISTS ".$wce_orders."( id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					order_id MEDIUMINT(9) NOT NULL,
					synced TINYINT(1) DEFAULT FALSE NOT NULL,
					UNIQUE KEY id (id)
			);";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
			
			$sql = "CREATE TABLE IF NOT EXISTS ".$wce_customers."( id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					user_id MEDIUMINT(9) NOT NULL,
					customer_number MEDIUMINT(9) NOT NULL,
					synced TINYINT(1) DEFAULT FALSE NOT NULL,
					UNIQUE KEY user_id (id)
			);";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
			
			update_option('economic_version', '1.2');
		}
		
		/**
		 * Drops tables for WooCommerce Economic
		 *
		 * @access public
		 * @param void
		 * @return bool
		 */
		function economic_uninstall(){
			global $wpdb;				
			$wce_orders = "wce_orders";
			$wce_customers = "wce_customers";
			$wpdb->query ("DROP TABLE ".$wce_orders.";");
			$wpdb->query ("DROP TABLE ".$wce_customers.";");
			delete_option('economic-tour');	
			delete_option('economic_version');
			delete_option('woocommerce_economic_general_settings');	
			delete_option('local_key_economic_plugin');
			delete_option('woocommerce_economic_order_settings');		
		}
		
		/**
		 *
		 *Functon for plugin update
		*/
		function economic_update(){
			global $wpdb;
			$table_name = "wce_orders";
			$economic_version = get_option('economic_version');
			if($economic_version != '' && floatval($economic_version) < 1.2 ){
				
			}
			update_option('economic_version', '1.2');
		}
		
		//add_action( 'plugins_loaded', 'economic_update' );
		
		// install necessary tables
		register_activation_hook( __FILE__, 'economic_install');
		register_uninstall_hook( __FILE__, 'economic_uninstall');
		//Section for plugin installation and activation ends


        /**
         * Localisation
         **/
		 
		 /**
		 * Return the locale to en_GB
		 */ 		
		add_action('plugins_loaded', 'economic_load_textdomain');
		function economic_load_textdomain() {
			load_plugin_textdomain( 'woocommerce-e-conomic-integration', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
				

        class WC_Economic {

            private $general_settings_key = 'woocommerce_economic_general_settings';
            private $order_settings_key = 'woocommerce_economic_order_settings';
            private $support_key = 'woocommerce_economic_support';
            private $manual_action_key = 'woocommerce_economic_manual_action';
            private $start_action_key = 'woocommerce_economic_start_action';
            private $general_settings;
            private $accounting_settings;
            private $plugin_options_key = 'woocommerce_economic_options';
            private $plugin_settings_tabs = array();

            public function __construct() {

                //call register settings function
                add_action( 'init', array( &$this, 'load_settings' ) );
                add_action( 'admin_init', array( &$this, 'register_woocommerce_economic_start_action' ));
                add_action( 'admin_init', array( &$this, 'register_woocommerce_economic_general_settings' ));
                add_action( 'admin_init', array( &$this, 'register_woocommerce_economic_manual_action' ));
                add_action( 'admin_init', array( &$this, 'register_woocommerce_economic_support' ));
                add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );


                // install necessary tables
                //register_activation_hook( __FILE__, array(&$this, 'install'));
                //register_deactivation_hook( __FILE__, array(&$this, 'uninstall'));
            }

            /***********************************************************************************************************
             * ADMIN SETUP
             ***********************************************************************************************************/

            /**
             * Adds admin menu
             *
             * @access public
             * @param void
             * @return void
             */
            function add_admin_menus() {
				add_menu_page( 'WooCommerce e-conomic Integration', 'e-conomic', 'manage_options', $this->plugin_options_key, array( &$this, 'woocommerce_economic_options_page' ) );
            }

            /**
             * Generates html for textfield for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_gateway($args) {
                $options = get_option($args['tab_key']);?>

                <input type="hidden" name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]" value="<?php echo $args['key']; ?>" />

                <select name="<?php echo $args['tab_key']; ?>[<?php echo $args['key'] . "_payment_method"; ?>]" >';
                    <option value=""<?php if(isset($options[$args['key'] . "_payment_method"]) && $options[$args['key'] . "_payment_method"] == ''){echo 'selected="selected"';}?>>Välj nedan</option>
                    <option value="CARD"<?php if(isset($options[$args['key'] . "_payment_method"]) && $options[$args['key'] . "_payment_method"] == 'CARD'){echo 'selected="selected"';}?>>Kortbetalning</option>
                    <option value="BANK"<?php if(isset($options[$args['key'] . "_payment_method"]) && $options[$args['key'] . "_payment_method"] == 'BANK'){echo 'selected="selected"';}?>>Bankgiro/Postgiro</option>
                </select>
                <?php
                $str = '';
                if(isset($options[$args['key'] . "_book_keep"])){
                    if($options[$args['key'] . "_book_keep"] == 'on'){
                        $str = 'checked = checked';
                    }
                }
                ?>
                <span>Bokför automatiskt:  </span>
                <input type="checkbox" name="<?php echo $args['tab_key']; ?>[<?php echo $args['key'] . "_book_keep"; ?>]" <?php echo $str; ?> />

            <?php
            }

            /**
             * Generates html for textfield for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_text($args) {
                $options = get_option($args['tab_key']);
                $val = '';
                if(isset($options[$args['key']] )){
                    $val = esc_attr( $options[$args['key']] );
                }
                ?>
                <input <?php echo isset($args['id'])? 'id="'.$args['id'].'"': ''; ?> type="text" name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]" value="<?php echo $val; ?>" />
                <span><i><?php echo $args['desc']; ?></i></span>
            <?php
            }
            
            /**
             * Generates html for dropdown for given settings of sandbox params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_mode_dropdown($args) {
                $options = get_option($args['tab_key']);
                $str = '';
                $str2 = '';
                if(isset($options[$args['key']])){
                    if($options[$args['key']] == 'Live'){
                        $str = 'selected';
                    }
                    else
                    {
                        $str2 = 'selected';
                    }
                }

                ?>
                <select <?php echo isset($args['id'])? 'id="'.$args['id'].'"': ''; ?> name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]">
                    <option <?php echo $str; ?>>Live</option>
                    <option <?php echo $str2; ?>>Sandbox</option>
                </select>
                <span id="sandbox-mode"><i><?php echo $args['desc']; ?></i></span>
            <?php
            }
            
            /**
             * Generates html for dropdown for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_dropdown($args) {
                $options = get_option($args['tab_key']);
                $str1 = '';
                $str2 = '';
                if(isset($options[$args['key']])){
                    if($options[$args['key']] == 'invoice'){
                        $str1 = 'selected';
                    }
					elseif($options[$args['key']] == 'order'){
						$str2 = 'selected';
					}
                }

                ?>
                <select <?php echo isset($args['id'])? 'id="'.$args['id'].'"':''; ?> name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]">
                	<option <?php echo $str1; ?> value='invoice'><?php echo __('Create invoice', 'woocommerce-e-conomic-integration'); ?></option>
                    <option <?php echo $str2; ?> value='order'><?php echo __('Create order', 'woocommerce-e-conomic-integration'); ?></option>
                </select>
                <span><i><?php echo $args['desc']; ?></i></span>
            <?php
            }
			
			
			/**
             * Generates html for dropdown for given settings params (product and customer group)
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_group($args) {
				$options = get_option('woocommerce_economic_general_settings');
				$wce_api = new WCE_API();
				$client = $wce_api->woo_economic_client();
				if(!$client){
					echo "<span><i>e-conomic client not loaded properly, please refresh the page to load properly.</i></span>";
					return false;
				}
				if($args['key'] == 'product-group')
					$groups = $client->ProductGroup_GetAll()->ProductGroup_GetAllResult->ProductGroupHandle;
				if($args['key'] == 'customer-group')
					$groups = $client->DebtorGroup_GetAll ()->DebtorGroup_GetAllResult->DebtorGroupHandle;
				
				?>
                <select name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]">
                <?php
				foreach($groups as $group){
                ?>
                    
                        <option <?php if(isset($options[$args['key']]) && $options[$args['key']] == $group->Number) echo 'selected'; ?> value='<?php echo $group->Number; ?>'><?php echo $group->Number; ?></option>
                 
            <?php
				}
				?>
                </select>
                    <span><i><?php echo $args['desc']; ?></i></span>
                <?php
            }


            /**
             * Generates html for checkbox for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_checkbox($args) {
                $options = get_option($args['tab_key']);
                $str = '';
                if(isset($options[$args['key']])){
                    if($options[$args['key']] == 'on'){
                        $str = 'checked = checked';
                    }
                }

                ?>
                <input <?php echo isset($args['id'])? 'id="'.$args['id'].'"': ''; ?> type="checkbox" name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]" <?php echo $str; ?> />
                <span><i><?php echo $args['desc']; ?></i></span>
            <?php
            }

            /**
             * WooCommerce Loads settigns
             *
             * @access public
             * @param void
             * @return void
             */
            function load_settings() {
                $this->general_settings = (array) get_option( $this->general_settings_key );
                $this->order_settings = (array) get_option( $this->order_settings_key );
            }

            /**
             * Tabs and plugin page setup
             *
             * @access public
             * @param void
             * @return void
             */
            function plugin_options_tabs() {
                $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->start_action_key;
                $options = get_option('woocommerce_economic_general_settings');
                echo '<div class="wrap"><h2>WooCommerce e-conomic Integration</h2><div id="icon-edit" class="icon32"></div></div>';
                $key_status = $this->is_license_key_valid();
                if(!isset($options['license-key']) || $options['license-key'] == '' || $key_status!='Active'){
                    echo "<button type=\"button button-primary\" class=\"button button-primary\" title=\"\" style=\"margin:5px\" onclick=\"window.open('http://whmcs.onlineforce.net/cart.php?a=add&pid=56&carttpl=flex-web20cart&language=English','_blank');\">".__('Get license Key', 'woocommerce-e-conomic-integration')."</button> <div class='key_error'>".__('License Key Invalid', 'woocommerce-e-conomic-integration')."</div>";

                }

                echo '<h2 class="nav-tab-wrapper">';

                foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
                    $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
                    echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
                }
                echo '</h2>';

            }

            /**
             * WooCommerce Billogram General Settings
             *
             * @access public
             * @param void
             * @return void
             */
            function register_woocommerce_economic_general_settings() {

                $this->plugin_settings_tabs[$this->general_settings_key] = __('General settings', 'woocommerce-e-conomic-integration');
				
				$options = get_option('woocommerce_economic_general_settings');

                register_setting( $this->general_settings_key, $this->general_settings_key );
                add_settings_section( 'section_general', __('General settings', 'woocommerce-e-conomic-integration'), array( &$this, 'section_general_desc' ), $this->general_settings_key );
                add_settings_field( 'woocommerce-economic-license-key', __('License key', 'woocommerce-e-conomic-integration'), array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'id' => 'license-key', 'tab_key' => $this->general_settings_key, 'key' => 'license-key', 'desc' => __('This is the License key you received from us by mail.', 'woocommerce-e-conomic-integration')) );
				
				add_settings_field( 'woocommerce-economic-token', __('Token ID', 'woocommerce-e-conomic-integration'), array( &$this, 'field_option_text'), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'token', 'desc' => __('Token access ID from e-conomic.', 'woocommerce-e-conomic-integration')) );
				
				add_settings_field( 'woocommerce-economic-appToken', __('Private app ID', 'woocommerce-e-conomic-integration'), array( &$this, 'field_option_text'), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'appToken', 'desc' => __('Private app ID from e-conomic.', 'woocommerce-e-conomic-integration')) );
				
				//add_settings_field( 'woocommerce-economic-agreementNumber', 'Avtalsnr.', array( &$this, 'field_option_text'), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'agreementNumber', 'desc' => 'Här anges din avtalsnr. från e-conomic.') );
				
				//add_settings_field( 'woocommerce-economic-agreementNumber', 'Avtalsnr.', array( &$this, 'field_option_text'), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'agreementNumber', 'desc' => 'Här anges din avtalsnr. från e-conomic.') );
				
                //add_settings_field( 'woocommerce-economic-username', 'Användar-ID', array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'username', 'desc' => 'Här anges din användar ID från e-conomic.') );
				
                //add_settings_field( 'woocommerce-economic-password', 'Lösenord', array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'password', 'desc' => 'Här anges din lösenord från e-conomic.') );
				
				//add_settings_field( 'woocommerce-economic-sync-option', 'e-conomic synk alternativ', array( &$this, 'field_option_dropdown' ), $this->general_settings_key, 'section_general', array ( 'id' => 'sync-option', 'tab_key' => $this->general_settings_key, 'key' => 'sync-option', 'desc' => 'Välj vilken enhet som ska skapas på e-conomic') );
				
				//add_settings_field( 'woocommerce-economic-cashbook', 'Aktivera kassaböckerna', array( &$this, 'field_option_checkbox' ), $this->general_settings_key, 'section_general', array ( 'id' => 'activate-cashbook', 'tab_key' => $this->general_settings_key, 'key' => 'activate-cashbook', 'desc' => 'Skapa debattör betalnings matchande fakturabeloppet efter att ha skapat fakturan.') );
				
                //add_settings_field( 'woocommerce-economic-cashbook-name', 'Kassaböckerna namn', array( &$this, 'field_option_text'), $this->general_settings_key, 'section_general', array ( 'id' => 'cashbook-name', 'tab_key' => $this->general_settings_key, 'key' => 'cashbook-name', 'desc' => 'Välj kassaböckerna att lägga gäldenärens betalningar.'));
				
				add_settings_field( 'woocommerce-economic-activate-allsync', __('Activate all orders sync', 'woocommerce-e-conomic-integration'), array( &$this, 'field_option_checkbox' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'activate-allsync', 'desc' => __('Sync all orders from WooCommerce e-conomic, regardless of whether the customer chooses another payment option (eg; Paypal, Dibs, Stripe, Payson, etc.) <br><i style="margin-left:25px; color: #F00;">If you are unsure what to choose here, we recommend you do not select this option.</i>', 'woocommerce-e-conomic-integration')) );
				
				add_settings_field( 'woocommerce-economic-sync-order-invoice', __('Create', 'woocommerce-e-conomic-integration'), array( &$this, 'field_option_dropdown' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'sync-order-invoice', 'desc' => __('Create e-conomic order or invoice?', 'woocommerce-e-conomic-integration')) );
				
				if($options['token'] != ''){
					add_settings_field( 'woocommerce-economic-product-group', __('Product group', 'woocommerce-e-conomic-integration'), array( &$this, 'field_option_group' ), $this->general_settings_key, 'section_general', array ( 'id' => 'product-group', 'tab_key' => $this->general_settings_key, 'key' => 'product-group', 'desc' => __('e-conomic product group to which new products are added.', 'woocommerce-e-conomic-integration')) );
					
					add_settings_field( 'woocommerce-economic-product-prefix', __('Product prefix', 'woocommerce-e-conomic-integration'), array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'id' => 'product-prefix', 'tab_key' => $this->general_settings_key, 'key' => 'product-prefix', 'desc' => __('Prefix added to the products stored to e-conomic from woocommerce', 'woocommerce-e-conomic-integration')) );
					
					add_settings_field( 'woocommerce-economic-customer-group', __('Customer group', 'woocommerce-e-conomic-integration'), array( &$this, 'field_option_group' ), $this->general_settings_key, 'section_general', array ( 'id' => 'customer-group', 'tab_key' => $this->general_settings_key, 'key' => 'customer-group', 'desc' => __('e-conomic customer group to which new customers are added.', 'woocommerce-e-conomic-integration')) );
				}
				//add_settings_field( 'woocommerce-economic-customer-prefix', 'Kund prefix', array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'id' => 'customer-prefix', 'tab_key' => $this->general_settings_key, 'key' => 'customer-prefix', 'desc' => 'Prefix läggs till kunder sparade till e-conomic från woocommerce') );
				//add_settings_field( 'woocommerce-economic-shipping-id', 'Frakt produktnummer', array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'id' => 'shipping-product-id', 'tab_key' => $this->general_settings_key, 'key' => 'shipping-product-id', 'desc' => 'Denna produkt numret läggs till alla fakturor som produktnummer för sjöfarten') );
            }


            /**
             * WooCommerce Manual Actions Settings
             *
             * @access public
             * @param void
             * @return void
             */
            function register_woocommerce_economic_manual_action() {

                $this->plugin_settings_tabs[$this->manual_action_key] = __('Manual functions', 'woocommerce-e-conomic-integration');
                register_setting( $this->manual_action_key, $this->manual_action_key );
            }


            /**
             * WooCommerce Start Actions
             *
             * @access public
             * @param void
             * @return void
             */
            function register_woocommerce_economic_start_action() {
                $this->plugin_settings_tabs[$this->start_action_key] = __('Welcome!', 'woocommerce-e-conomic-integration');
                register_setting( $this->start_action_key, $this->start_action_key );
            }


            /**
             * WooCommerce Billogram Accounting Settings
             *
             * @access public
             * @param void
             * @return void
             */
            function register_woocommerce_economic_support() {

                $this->plugin_settings_tabs[$this->support_key] = __('Support', 'woocommerce-e-conomic-integration');
                register_setting( $this->support_key, $this->support_key );
            }

            /**
             * The description for the general section
             *
             * @access public
             * @param void
             * @return void
             */
            function section_general_desc() { echo __('Specifies basic settings for the e-conomic integration and you can control which parts you want to sync to e-conomic', 'woocommerce-e-conomic-integration'); }

            /**
             * The description for the accounting section
             *
             * @access public
             * @param void
             * @return void
             */
            function section_accounting_desc() { echo __('Description Accounting settings.', 'woocommerce-e-conomic-integration'); }

            /**
             * The description for the shipping section
             *
             * @access public
             * @param void
             * @return void
             */
            function section_order_desc() { echo ''; }

            /**
             * Options page
             *
             * @access public
             * @param void
             * @return void
             */
            function woocommerce_economic_options_page() {
                $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->start_action_key;?>

                <!-- CSS -->
                <style>
                    li.logo,  {
                        float: left;
                        width: 100%;
                        padding: 20px;
                    }
                    li.full {
	                    padding: 10px 0;
                        height: 50px;
                    }
                    li.full img, img.test_load{
                        float: left;
                        margin: -5px 0 0 5px;
                        display: none;
                    }
					span.test_warning{
						float: left;
						margin:25px 0px 0px 10px;
					}
                    li.col-two {
                        float: left;
                        width: 380px;
                        margin-left: 1%;
                    }
                    li.col-onethird, li.col-twothird {
	                    float: left;
                    }
                    li.col-twothird {
	                    max-width: 772px;
	                    margin-right: 20px;
                    }
                    li.col-onethird {
	                    width: 300px;
                    }
                    .mailsupport {
	                	background: #dadada;
	                	border-radius: 4px;
	                	-moz-border-radius: 4px;
	                	-webkit-border-radius: 4px;
	                	max-width: 230px;
	                	padding: 0 0 20px 20px;
	                }
	                .mailsupport > h2 {
		                font-size: 20px;
		            }
	                form#support table.form-table tbody tr td, form#installationSupport table.form-table tbody tr td {
		                padding: 4px 0 !important;
		            }
		            form#support input, form#support textarea, form#installationSupport input, form#support textarea {
			                border: 1px solid #b7b7b7;
			                border-radius: 3px;
			                -moz-border-radius: 3px;
			                -webkit-border-radius: 3px;
			                box-shadow: none;
			                width: 210px;
			        }
			        form#support textarea, form#installationSupport textarea {
				        height: 60px;
			        }
			        form#support button, form#installationSupport button {
				        float: left;
				        margin: 0 !important;
				        min-width: 100px;
				    }
				    ul.manuella li.full button.button {
					       clear: left;
					       float: left;
					       min-width: 250px;
				    }
				    ul.manuella li.full > p {
					        clear: right;
					        float: left;
					        margin: 2px 0 20px 11px;
					        max-width: 440px;
					        padding: 5px 10px;
					}
					.key_error
					{
						 background-color: white;
					    color: red;
					    display: inline;
					    font-weight: bold;
					    margin-top: 5px;
					    padding: 5px;
					    position: absolute;
					    text-align: center;
					    width: 200px;
					}
					.testConnection{
						float:left;
					}
					
					p.submit{
						float: left;
						width: auto;
						padding: 0px;
					}
					/*li.wp-first-item{
						display:none;
					}*/
					span#sandbox-mode{
						color:#F00
					}
					span.error{
						color:#F00
					}
                </style>
                <script type="text/javascript">
					jQuery(document).ready(function() {
						var element = jQuery('#cashbook-name').parent().parent();
						if(jQuery('#activate-cashbook').is(':checked')){
							element.show();
						}else{
							element.hide();
						}
						jQuery('#activate-cashbook').change(function() {
							if(this.checked) {
								element.show(300);							
							}else{
								element.hide(300);
							}
						});
						
						});
						
						jQuery("#license-key").live("keyup", function(){
							var str = jQuery("#license-key").val();
							var patt = /wem-[a-zA-Z0-9][^\W]+/gi;
							var licenseMatch = patt.exec(str);
							if(licenseMatch){
								licenseMatch = licenseMatch.toString();
								if(licenseMatch.length == 24){
									jQuery("#license-key").next().removeClass("error");
									jQuery("#license-key").next().children("i").html("Här anges License-nyckeln du har erhållit från oss via mail.");
								}else{
									jQuery("#license-key").next().children("i").html("Ogiltigt format");
									jQuery("#license-key").next().addClass("error");
								}
							}else{
								jQuery("#license-key").next().children("i").html("Ogiltigt format");
								jQuery("#license-key").next().addClass("error");
							}
						});
				</script>
                <?php
                if($tab == $this->support_key){ ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <ul>
                            <li class="logo"><?php echo '<img src="' . plugins_url( 'img/logo_landscape.png', __FILE__ ) . '" > '; ?></li>
                            <li class="col-two"><a href="http://wooconomics.com/category/faq/"><?php echo '<img src="' . plugins_url( 'img/awp_faq.png', __FILE__ ) . '" > '; ?></a></li>
                            <li class="col-two"><a href="http://wooconomics.com/"><?php echo '<img src="' . plugins_url( 'img/awp_support.png', __FILE__ ) . '" > '; ?></a></li>
                        </ul>
                    </div>
                <?php
                }
                else if($tab == $this->general_settings_key){ ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <form method="post" action="options.php">
                            <?php wp_nonce_field( 'update-options' ); ?>
                            <?php settings_fields( $tab ); ?>
                            <?php do_settings_sections( $tab ); ?>
                            <?php submit_button(__('Save changes', 'woocommerce-e-conomic-integration')); ?>
                            <button style="margin: 20px 0px 0px 10px;" type="button" name="testConnection" class="button button-primary testConnection" onclick="test_connection()" /><?php echo __('Test connection', 'woocommerce-e-conomic-integration'); ?></button>
                            <span class="test_warning"><?php echo __('NOTE! Save changes before testing the connection', 'woocommerce-e-conomic-integration'); ?></span>
                            <img style="margin: 10px 0px 0px 10px;" src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="test_load" >
                        </form>
                    </div>
                <?php }
                else if($tab == $this->manual_action_key){ ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <ul class="manuella">
                            <li class="full">
                                <button type="button" class="button" title="Manuell synkning kontakter" style="margin:5px" onclick="sync_contacts('<?php _e('The synchronization can take a long time depending on how many customers to be imported. \ nA message will appear on this page when the synchronization is complete. Do not leave this page, which will suspended the import!', 'woocommerce-e-conomic-integration') ?>')"><?php _e('Manual sync contacts', 'woocommerce-e-conomic-integration'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="customer_load" >
                                <p><?php _e('Sync customers created manually in woocommerce dashboard.', 'woocommerce-e-conomic-integration'); ?></p>
                            </li>
                            <li class="full">
                                <button type="button" class="button" title="Manuell Synkning beställningar/fakturor" style="margin:5px" onclick="sync_orders('<?php _e('The synchronization can take a long time depending on how many orders to be exported. \ nA message will appear on this page when the synchronization is complete. Do not leave this page, which will suspended the import!', 'woocommerce-e-conomic-integration') ?>')"><?php _e('Manual syncing orders/invoices', 'woocommerce-e-conomic-integration'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="order_load" >
                                <p><?php _e('Synchronizes all orders that failed to synchronize. (default sync is set to General Settings-> Create options)', 'woocommerce-e-conomic-integration'); ?></p>
                            </li>
                            <li class="full">
                                <button type="button" class="button" title="Manuell Synkning produkter" style="margin:5px" onclick="sync_products('<?php _e('The synchronization can take a long time depending on how many products that will be exported. \ nA message will appear on this page when the synchronization is complete. Do not leave this page, which will suspended the import!', 'woocommerce-e-conomic-integration') ?>')"><?php _e('Manual sync products', 'woocommerce-e-conomic-integration'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="product_load" >
                                <p><?php _e('Send all products to your e-conomic. If you have many products, it may take a while.', 'woocommerce-e-conomic-integration'); ?></p>
                            </li>
                            <li class="full">
                                <button type="button" class="button" title="Manuell Synkning Shipping" style="margin:5px" onclick="sync_shippings('<?php _e('A message will appear on this page when the synchronization is complete. Do not leave this page, which will suspended the import!', 'woocommerce-e-conomic-integration') ?>')"><?php _e('Manual sync delivery methods', 'woocommerce-e-conomic-integration'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="shipping_load" >
                                <p><?php _e('Send all delivery method costs to your e-conomic.', 'woocommerce-e-conomic-integration'); ?></p>
                            </li>
                        </ul>
                        <div class="clear"></div>
                    	<div id="result"></div>
                    </div>
                <?php }
                else if($tab == $this->start_action_key){
                    $options = get_option('woocommerce_economic_general_settings');
                    ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <ul>
                        	<li>
                        		<?php echo '<img src="' . plugins_url( 'img/banner-772x250.png', __FILE__ ) . '" > '; ?>
                        	</li>
                            <li class="col-twothird">
                                <iframe src="//player.vimeo.com/video/38627647" width="500" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
                            </li>
                            <?php if(!isset($options['license-key']) || $options['license-key'] == ''){ ?>
                            <li class="col-onethird">
                            	<div class="mailsupport">
                            		<h2><?php echo __('Installation Support', 'woocommerce-e-conomic-integration'); ?></h2>
                            	    <form method="post" id="installationSupport">
                            	        <input type="hidden" value="send_support_mail" name="action">
                            	        <table class="form-table">
								
                            	            <tbody>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Company', 'woocommerce-e-conomic-integration'); ?>" name="company">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Name', 'woocommerce-e-conomic-integration'); ?>" name="name">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Phone', 'woocommerce-e-conomic-integration'); ?>" name="telephone">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Email', 'woocommerce-e-conomic-integration'); ?>" name="email">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <textarea placeholder="<?php echo __('Subject', 'woocommerce-e-conomic-integration'); ?>" name="subject"></textarea>
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <button type="button" class="button button-primary" title="send_support_mail" style="margin:5px" onclick="send_support_mail('installationSupport')"><?php echo __('Send', 'woocommerce-e-conomic-integration'); ?></button>
                            	                </td>
                            	            </tr>
                            	            </tbody>
                            	        </table>
                            	        <!-- p class="submit">
                            	           <button type="button" class="button button-primary" title="send_support_mail" style="margin:5px" onclick="send_support_mail()">Skicka</button> 
                            	        </p -->
                            	    </form>
                            	</div>
                            </li>
                        <?php } else{ ?>
                        	<li class="col-onethird">
                            	<div class="mailsupport">
                            		<h2><?php echo __('Support', 'woocommerce-e-conomic-integration'); ?></h2>
                            	    <form method="post" id="support">
                            	        <input type="hidden" value="send_support_mail" name="action">
                            	        <table class="form-table">
								
                            	            <tbody>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Company', 'woocommerce-e-conomic-integration'); ?>" name="company">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Name', 'woocommerce-e-conomic-integration'); ?>" name="name">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Phone', 'woocommerce-e-conomic-integration'); ?>" name="telephone">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Email', 'woocommerce-e-conomic-integration'); ?>" name="email">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <textarea placeholder="<?php echo __('Subject', 'woocommerce-e-conomic-integration'); ?>" name="subject"></textarea>
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                                                	<input type="hidden" name="supportForm" value="support" />
                            	                    <button type="button" class="button button-primary" title="send_support_mail" style="margin:5px" onclick="send_support_mail('support')"><?php echo __('Send', 'woocommerce-e-conomic-integration'); ?></button>
                            	                </td>
                            	            </tr>
                            	            </tbody>
                            	        </table>
                            	        <!-- p class="submit">
                            	           <button type="button" class="button button-primary" title="send_support_mail" style="margin:5px" onclick="send_support_mail()">Skicka</button> 
                            	        </p -->
                            	    </form>
                            	</div>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                <?php }
                else{ ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <form method="post" action="options.php">
                            <?php wp_nonce_field( 'update-options' ); ?>
                            <?php settings_fields( $tab ); ?>
                            <?php do_settings_sections( $tab ); ?>
                            <?php submit_button(); ?>
                        </form>
                    </div>
                <?php }
            }	

           

            /***********************************************************************************************************
             * WP-PLUGS API FUNCTIONS
             ***********************************************************************************************************/

            /**
             * Checks if license-key is valid
             *
             * @access public
             * @return void
             */
            public function is_license_key_valid() {
                include_once("class-economic-api.php");
                $wce_api = new WCE_API();
                $result = $wce_api->create_license_validation_request();
                switch ($result['status']) {
		            case "Active":
		                // get new local key and save it somewhere
		                $localkeydata = $result['localkey'];
		                update_option( 'local_key_economic_plugin', $localkeydata );
		                return $result['status'];
		                break;
		            case "Invalid":
		                logthis("License key is Invalid");
		            	return $result['status'];
		                break;
		            case "Expired":
		                logthis("License key is Expired");
                        return $result['status'];
		                break;
		            case "Suspended":
		                logthis("License key is Suspended");
		                return $result['status'];
		                break;
		            default:
                        logthis("Invalid Response");
		                break;
	        	}
            }
        }
        $GLOBALS['wc_consuasor'] = new WC_Economic();
    }
}