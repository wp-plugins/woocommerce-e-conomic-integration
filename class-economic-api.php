<?php
//php.ini overriding necessary for communicating with the SOAP server.
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(1);
ini_set("default_socket_timeout", 6000);
class WCE_API{

    /** @public String base URL */
    public $api_url;
	
	/** @public String license key */
    public $license_key;
    
    /** @public String Agreement Number */
    //public $agreementNumber;

    /** @public String User Name */
    //public $username;

    /** @public String Password */
    //public $password;

    /** @public String access ID or token */
    public $token;
	
	/** @public String private access ID or appToken */
    public $appToken;
	
	/** @public String local key data */
    public $localkeydata;
	
	/** @public Number corresponding the product group */
    public $product_group;
	
	/** @public alphanumber corresponding the product offset */
    public $product_offset;
	
	/** @public Number corresponding the customer group */
    public $customer_group;
	
	/** @public alphanumber corresponding the customer offset */
    //public $customer_offset;
	
	/** @public string yes/no */
    public $activate_allsync;
	
	
	/** @public array including all the customer meta fiedls that are snyned */
	public $user_fields = array(
	  'billing_phone',
	  'billing_email',
	  'billing_country',
	  'billing_address_1',
	  'billing_address_2',
	  'billing_state',
	  'billing_postcode',
	  'billing_city',
	  'billing_country',
	  'billing_company',
	  'billing_last_name',
	  'billing_first_name',
	  'vat_number',
	
	  'shipping_phone',
	  'shipping_email',
	  'shipping_country',
	  'shipping_address_1',
	  'shipping_address_2',
	  'shipping_state',
	  'shipping_postcode',
	  'shipping_city',
	  'shipping_country',
	  'shipping_company',
	  'shipping_last_name',
	  'shipping_first_name'
	);
	
	public $product_lock;
	
	//public $shipping_product_id;

    /**
     *
     */
    function __construct() {

        $options = get_option('woocommerce_economic_general_settings');
		
        $this->localkeydata = get_option('local_key_economic_plugin');
        $this->api_url = dirname(__FILE__)."/EconomicWebservice.asmx.xml";
		//$this->api_url = 'https://api.e-conomic.com/secure/api1/EconomicWebservice.asmx?WSDL';
        $this->license_key = $options['license-key'];
        //$this->agreementNumber = $options['agreementNumber'];
        //$this->username = $options['username'];
        //$this->password = $options['password'];
		
		$this->token = $options['token'];
		$this->appToken = $options['appToken'];
		
		$this->product_group = isset($options['product-group'])? $options['product-group']: '';
		$this->product_offset = isset($options['product-prefix'])? $options['product-prefix']: '';
		$this->customer_group = isset($options['customer-group'])? $options['customer-group']: '';
		if(isset($options['activate-allsync'])){
			$this->activate_allsync = $options['activate-allsync'];
		}
		//$this->customer_offset = $options['customer-prefix'];
		
		$this->product_lock = false;
		
		//$this->shipping_product_id = $options['shipping-product-id'];
    }

    /**
     * Create Connection to e-conomic
     *
     * @access public
     * @return object
     */
    public function woo_economic_client(){
	
	  $client = new SoapClient($this->api_url, array("trace" => 1, "exceptions" => 1));
	
	  logthis("woo_economic_client loaded token: " . $this->token . " appToken: " . $this->appToken);
	  if (!$this->token || !$this->appToken)
		die("e-conomic access ID(token), and private access token(appToken) are not defined");
		
	  logthis("woo_economic_client - options are OK!");
	  logthis("woo_economic_client - creating client...");
	  	  
	  try{
		 $client->ConnectWithToken(array(
			'token' 	=> $this->token,
			'appToken'  => $this->appToken));
	  }
	  catch (Exception $exception){
		logthis("Connection to client failed: " . $exception->getMessage());
		$this->debug_client($client);
		return false;
	  }
	  
	  logthis("woo_economic_client - client created");
	  return $client;
	}
	
	/**
     * Log the client connection request headers for debugging
     *
     * @access public
     * @return void
     */
	public function debug_client($client){
	  if (is_null($client)) {
		logthis("Client is null");
	  } else {
		logthis("-----LastRequestHeaders-------");
		logthis($client->__getLastRequestHeaders());
		logthis("------LastRequest------");
		logthis($client->__getLastRequest());
		logthis("------LastResponse------");
		logthis($client->__getLastResponse());
		logthis("------Debugging ends------");
	  }
	}

    /**
     * Creates a e-conomic HttpRequest
     *
     * @access public
     * @return bool
     */
    public function create_API_validation_request(){
		//logthis(get_option('woocommerce_economic_general_settings'));
        logthis("API VALIDATION");
        if(!isset($this->license_key)){
			logthis("API VALIDATION FAILED: license key not set!");
            return false;
        }
		
		if($this->woo_economic_client()){
			return true;
		}
		else{
			logthis("API VALIDATION FAILED: client not connected!");
			return false;
		}
    }

    /**
     * Creates a HttpRequest and appends the given XML to the request and sends it For license key
     *
     * @access public
     * @return bool
     */
    public function create_license_validation_request($localkey=''){
        logthis("LICENSE VALIDATION");
        if(!isset($this->license_key)){
            return false;
        }
        $licensekey = $this->license_key;
        // -----------------------------------
        //  -- Configuration Values --
        // -----------------------------------
        // Enter the url to your WHMCS installation here
        //$whmcsurl = 'http://176.10.250.47/whmcs/';
        $whmcsurl = 'http://whmcs.onlineforce.net/';
        // Must match what is specified in the MD5 Hash Verification field
        // of the licensing product that will be used with this check.
        $licensing_secret_key = 'ak4762';
        //$licensing_secret_key = 'itservice';
        // The number of days to wait between performing remote license checks
        $localkeydays = 15;
        // The number of days to allow failover for after local key expiry
        $allowcheckfaildays = 5;

        // -----------------------------------
        //  -- Do not edit below this line --
        // -----------------------------------

        $check_token = time() . md5(mt_rand(1000000000, 9999999999) . $licensekey);
        $checkdate = date("Ymd");
        $domain = $_SERVER['SERVER_NAME'];
        $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
        $dirpath = dirname(__FILE__);
        $verifyfilepath = 'modules/servers/licensing/verify.php';
        $localkeyvalid = false;
        if ($localkey) {
            $localkey = str_replace("\n", '', $localkey); # Remove the line breaks
            $localdata = substr($localkey, 0, strlen($localkey) - 32); # Extract License Data
            $md5hash = substr($localkey, strlen($localkey) - 32); # Extract MD5 Hash
            if ($md5hash == md5($localdata . $licensing_secret_key)) {
                $localdata = strrev($localdata); # Reverse the string
                $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
                $localdata = substr($localdata, 32); # Extract License Data
                $localdata = base64_decode($localdata);
                $localkeyresults = unserialize($localdata);
                $originalcheckdate = $localkeyresults['checkdate'];
                if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                    $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                    if ($originalcheckdate > $localexpiry) {
                        $localkeyvalid = true;
                        $results = $localkeyresults;
                        $validdomains = explode(',', $results['validdomain']);
                        if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                        $validips = explode(',', $results['validip']);
                        if (!in_array($usersip, $validips)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                        $validdirs = explode(',', $results['validdirectory']);
                        if (!in_array($dirpath, $validdirs)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                    }
                }
            }
        }
        if (!$localkeyvalid) {
            $postfields = array(
                'licensekey' => $licensekey,
                'domain' => $domain,
                'ip' => $usersip,
                'dir' => $dirpath,
            );
            if ($check_token) $postfields['check_token'] = $check_token;
            $query_string = '';
            foreach ($postfields AS $k=>$v) {
                $query_string .= $k.'='.urlencode($v).'&';
            }
            if (function_exists('curl_exec')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $data = curl_exec($ch);
                curl_close($ch);
            } else {
                $fp = fsockopen($whmcsurl, 80, $errno, $errstr, 5);
                if ($fp) {
                    $newlinefeed = "\r\n";
                    $header = "POST ".$whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
                    $header .= "Host: ".$whmcsurl . $newlinefeed;
                    $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
                    $header .= "Content-length: ".@strlen($query_string) . $newlinefeed;
                    $header .= "Connection: close" . $newlinefeed . $newlinefeed;
                    $header .= $query_string;
                    $data = '';
                    @stream_set_timeout($fp, 20);
                    @fputs($fp, $header);
                    $status = @socket_get_status($fp);
                    while (!@feof($fp)&&$status) {
                        $data .= @fgets($fp, 1024);
                        $status = @socket_get_status($fp);
                    }
                    @fclose ($fp);
                }
            }
            if (!$data) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $results = $localkeyresults;
                } else {
                    $results = array();
                    $results['status'] = "Invalid";
                    $results['description'] = "Remote Check Failed";
                    return $results;
                }
            } else {
                preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
                $results = array();
                foreach ($matches[1] AS $k=>$v) {
                    $results[$v] = $matches[2][$k];
                }
            }
            if (!is_array($results)) {
                die("Invalid License Server Response");
            }
            if (isset($results['md5hash'])) {
                if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
                    $results['status'] = "Invalid";
                    $results['description'] = "MD5 Checksum Verification Failed";
                    return $results;
                }
            }
            if ($results['status'] == "Active") {
                $results['checkdate'] = $checkdate;
                $data_encoded = serialize($results);
                $data_encoded = base64_encode($data_encoded);
                $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
                $data_encoded = strrev($data_encoded);
                $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
                $data_encoded = wordwrap($data_encoded, 80, "\n", true);
                $results['localkey'] = $data_encoded;
            }
            $results['remotecheck'] = true;
        }
        unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
        return $results;
        //return true;
    }
	
	
	/**
     * Get product SKU, concatenate product offset if not synced from ecnomic.
     *
     * @access public
     * @param product oject
     * @return product SKU string.
     */
	
	
	public function woo_get_product_sku(WC_Product $product){ //todo fix this function.
	  $synced_from_economic = get_post_meta($product->id, 'synced_from_economic', true);
	  $product_sku = null;
	  if (isset($synced_from_economic) && $synced_from_economic) {
		$product_sku = $product->sku;
	  } else {
		$product_sku = $this->product_offset.$product->sku;
	  }
	  return $product_sku;
	}
	
	/**
     * Get product SKU from economic
     *
     * @access public
     * @param product oject
     * @return product SKU string.
     */
		
	public function woo_get_product_sku_from_economic($product_id){
		$product_offset = $this->product_offset;
		if (strpos($product_id, $this->product_offset) === false) // this is an woocommerce product
			return $this->product_offset.$product->sku;
		else
			return $product_id;
	}
	
	
	/**
     * Save WooCommerce Order to e-conomic
     *
     * @access public
     * @param product oject, user object, Soap client object, reference order ID and refund flag.
     * @return bool
     */
	public function save_invoice_to_economic(WP_User $user, WC_Order $order, SoapClient &$client, $refund){
		global $wpdb;
		logthis("save_invoice_to_economic Getting debtor handle");
		$debtor_handle = $this->woo_get_debtor_handle_from_economic($user, $client);
		if (!($debtor_handle)) {
			logthis("save_invoice_to_economic debtor not found, can not create invoice");
			return false;
		}
		try {
		
			$invoice_number = $this->woo_get_invoice_number_from_economic($client, $order->id);
			if (!$refund && isset($invoice_number)) {
				logthis("save_invoice_to_economic invoice already exists");
				return true;
			}
			
			$current_invoice_handle = $this->woo_get_current_invoice_from_economic($client, $order->id, $debtor_handle);
			logthis("save_invoice_to_economic woo_get_current_invoice_from_economic returned current invoice handle.");
			logthis($current_invoice_handle);
			
			$countries = new WC_Countries();
			
			$address = null;
			$city = null;
			$postalcode = null;
			$country = null;
			
			if (isset($order->shipping_address_1) || !empty($order->shipping_address_1)) {
				$formatted_state = $countries->states[$order->shipping_country][$order->shipping_state];
				$address = trim($order->shipping_address_1 . "\n" . $order->shipping_address_2 . "\n" . $formatted_state);
				$city = $order->shipping_city;
				$postalcode = $order->shipping_postcode;
				$country = $countries->countries[$order->shipping_country];
			} else {
				$formatted_state = $countries->states[$order->billing_country][$order->billing_state];
				$address = trim($order->billing_address_1 . "\n" . $order->billing_address_2 . "\n" . $formatted_state);
				$city = $order->billing_city;
				$postalcode = $order->billing_postcode;
				$country = $countries->countries[$order->billing_country];
			}
			
			
			logthis("save_invoice_to_economic CurrentInvoice_SetDeliveryAddress.");
			$client->CurrentInvoice_SetDeliveryAddress(array(
				'currentInvoiceHandle' => array('Id' => $current_invoice_handle->Id),
				'value' => $address
			));
			
			logthis("save_invoice_to_economic CurrentInvoice_SetDeliveryCity.");
			$client->CurrentInvoice_SetDeliveryCity(array(
				'currentInvoiceHandle' => array('Id' => $current_invoice_handle->Id),
				'value' => $city
			));
			
			logthis("save_invoice_to_economic CurrentInvoice_SetDeliveryPostalCode.");
			$client->CurrentInvoice_SetDeliveryPostalCode(array(
				'currentInvoiceHandle' => array('Id' => $current_invoice_handle->Id),
				'value' => $postalcode
			));
			
			logthis("save_invoice_to_economic CurrentInvoice_SetDeliveryCountry.");
			$client->CurrentInvoice_SetDeliveryCountry(array(
				'currentInvoiceHandle' => array('Id' => $current_invoice_handle->Id),
				'value' => $country
			));
			
			
			logthis("save_invoice_to_economic call woo_handle_invoice_lines_to_economic.");			
			$this->woo_handle_invoice_lines_to_economic($order, $current_invoice_handle, $client, $refund);
			
			
			
			//logthis("SELECT * FROM wce_orders WHERE order_id=".$order->id.": ".$wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id.";"));
		
			if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id.";")){
				$wpdb->update ("wce_orders", array('synced' => 1), array('order_id' => $order->id), array('%d'), array('%d'));
			}else{
				$wpdb->insert ("wce_orders", array('order_id' => $order->id, 'synced' => 1), array('%d', '%d'));
			}
			return true;
		} catch (Exception $exception) {
			logthis("save_invoice_to_economic could not save order: " . $exception->getMessage());
			$this->debug_client($client);
			logthis('Could not create invoice.');
			logthis($exception->getMessage());
			if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id." AND synced=0;")){
				return false;
			}else{
				$wpdb->insert ("wce_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
				return false;
			}
		}
	}

	
	/**
     * Get debtor handle from economic
     *
     * @access public
     * @param User object, SOAP client
     * @return debtor_handle object
     */
	public function woo_get_debtor_handle_from_economic(WP_User $user, SoapClient &$client){
		$debtorNumber = $user->get('debtor_number');
		logthis("woo_get_debtor_handle_from_economic trying to load : " . $debtorNumber);
		if (!isset($debtorNumber) || empty($debtorNumber)) {
			logthis("woo_get_debtor_handle_from_economic no handle found");
			return null;
		}
		
		$debtor_handle = $client->Debtor_FindByNumber(array(
		'number' => $debtorNumber
		))->Debtor_FindByNumberResult;
		
		if (isset($debtor_handle))
			logthis("woo_get_debtor_handle_from_economic debtor found for user->id " . $user->ID);
		else {
			logthis("woo_get_debtor_handle_from_economic debtor not found");
			return null;
		}
		
		return $debtor_handle;
	}
	
	/**
     * Get debtor delivery locations handle from economic
     *
     * @access public
     * @param User object, SOAP client
     * @return debtor_delivery_location_handles object
     */
	public function woo_get_debtor_delivery_location_handles_from_economic(WP_User $user, SoapClient &$client, $debtor_handle){
		
		//$debtor_handle = $this->woo_get_debtor_handle_from_economic($user, $client);
		
		if (!isset($debtor_handle) || empty($debtor_handle)) {
			logthis("woo_get_debtor_delivery_location_handles_from_economic no handle found");
			return null;
		}
		
		logthis("woo_get_debtor_delivery_location_handles_from_economic getting delivery locations available for debtor debtor_delivery_location_handles");
		//logthis($debtor_handle);
		$debtor_delivery_location_handles = $client->Debtor_GetDeliveryLocations(array(
		'debtorHandle' => $debtor_handle
		))->Debtor_GetDeliveryLocationsResult;
		
		//logthis("debtor_delivery_location_handles");
		//logthis($debtor_delivery_location_handles);
		
		if (isset($debtor_delivery_location_handles->DeliveryLocationHandle->Id)){
			logthis("woo_get_debtor_delivery_location_handles_from_economic delivery location handle ID: ");
			logthis($debtor_delivery_location_handles->DeliveryLocationHandle->Id);
			return $debtor_delivery_location_handles->DeliveryLocationHandle;
		}
		else {
			$debtor_delivery_location_handle = $client->DeliveryLocation_Create(array(
			'debtorHandle' => $debtor_handle
			))->DeliveryLocation_CreateResult;
			logthis("woo_get_debtor_delivery_location_handles_from_economic delivery location handle: ");
			logthis($debtor_delivery_location_handle);
			return $debtor_delivery_location_handle;
		}
	}
	
	/**
     * Get invoice number from economic
     *
     * @access public
     * @param User object, SOAP client
     * @return debtor_handle object
     */	
	public function woo_get_invoice_number_from_economic(SoapClient &$client, $reference){
		$handles = $client->Invoice_FindByOtherReference(array(
			'otherReference' => $reference
		))->Invoice_FindByOtherReferenceResult;
		
		$invoice_handle = null;
		foreach ($handles as $handle) {
			if (is_object($handle)) {
				$invoice_handle = $handle;
				logthis("woo_get_invoice_number_from_economic handle is object number: " . $invoice_handle->Number);
			}
			if (is_array($handle)) {
				foreach ($handle as $ihandle) {
					$invoice_handle = $ihandle;
					logthis("woo_get_invoice_number_from_economic handle is array number: " . $invoice_handle->Number);
					break;
				}
			}
		}
		
		if (isset($invoice_handle))
			logthis("woo_get_invoice_number_from_economic invoice " . $invoice_handle->Number . " exists");
		else
			logthis("woo_get_invoice_number_from_economic doesn't exist for ref. " . $reference);
		
		return $invoice_handle;
	}
	
	/**
     * Get current invoice from economic
     *
     * @access public
     * @param User object, SOAP client
     * @return current invoice handle object
     */	
	public function woo_get_current_invoice_from_economic(SoapClient &$client, $reference, &$debtor_handle){
		$current_invoice_handle = $client->CurrentInvoice_FindByOtherReference(array(
			'otherReference' => $reference
		))->CurrentInvoice_FindByOtherReferenceResult;
		
		if (!isset($current_invoice_handle->CurrentInvoiceHandle->Id)) {
			logthis("woo_get_current_invoice_from_economic create CurrentInvoiceHandle.");
			$current_invoice_handle = $client->CurrentInvoice_Create(array(
				'debtorHandle' => $debtor_handle
			))->CurrentInvoice_CreateResult;
			$client->CurrentInvoice_SetOtherReference(array(
				'currentInvoiceHandle' => $current_invoice_handle,
				'value' => $reference
			));
			logthis("woo_get_current_invoice_from_economic current invoice handle created is: " . $current_invoice_handle->Id);
			return $current_invoice_handle;
		}
		//logthis("current_invoice_handle: ".$current_invoice_handle);
		logthis("woo_get_current_invoice_from_economic invoice handle found and ID is: ".$current_invoice_handle->CurrentInvoiceHandle->Id);
		return $current_invoice_handle->CurrentInvoiceHandle;
	}
	
	
	/**
     * Get order lines handle
     *
     * @access public
     * @param Order object, Invoice handle object, SOAP client, refund bool
     * @return debtor_handle object
     */	
	public function woo_handle_invoice_lines_to_economic(WC_Order $order, $current_invoice_handle, SoapClient &$client, $refund){
	  logthis("woo_handle_invoice_lines_to_economic - get all lines");
	
	  foreach ($order->get_items() as $item) {
		$product = $order->get_product_from_item($item);
		//$line = $lines[$this->woo_get_product_sku($product)];
		$current_invoice_line_handle = null;
		$current_invoice_line_handle = $this->woo_create_currentinvoice_orderline_at_economic($current_invoice_handle, $this->woo_get_product_sku($product), $client);
	
		logthis("woo_handle_invoice_lines_to_economic updating qty on id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number);
		$quantity = ($refund) ? $item['qty'] * -1 : $item['qty'];
		$client->CurrentInvoiceLine_SetQuantity(array(
		  'currentInvoiceLineHandle' => $current_invoice_line_handle,
		  'value' => $quantity
		));
		logthis("woo_handle_invoice_lines_to_economic updated line");
	  }
	  
	    $shippingItem = reset($order->get_items('shipping'));
		//logthis($shippingItem['method_id']);
		if(isset($shippingItem['method_id'])){
			logthis("woo_handle_invoice_lines_to_economic adding Shipping line");
			$current_invoice_line_handle = null;
			$current_invoice_line_handle = $this->woo_create_currentinvoice_orderline_at_economic($current_invoice_handle, $shippingItem['method_id'], $client);
			logthis("woo_handle_invoice_lines_to_economic updating qty on id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number);
			$quantity = ($refund) ? $item['qty'] * -1 : 1;
			$client->CurrentInvoiceLine_SetQuantity(array(
			'currentInvoiceLineHandle' => $current_invoice_line_handle,
			'value' => $quantity
			));
			logthis("woo_handle_invoice_lines_to_economic updated shipping line");
		}
		
	  
	  
	  //Setting order percent to current invoice line
	  /*if($order->get_total_discount() > 0){
	  	$orderPercent = ($order->get_total() * $order->get_total_discount())/100;
		logthis("woo_handle_orderlines_to_economic set discount percent!");
		$client->CurrentInvoiceLine_SetDiscountAsPercent(array(
		  'currentInvoiceLineHandle' => $current_invoice_line_handle,
		  'value' => $orderPercent
		));
	  }*/
	  
	
	  /*if (empty($line_handles)) {
		logthis("woo_handle_orderlines_to_economic adding shipping order line: " . $shipping_product_id);
		$handle = $this->woo_create_currentinvoice_orderline_at_economic($current_invoice_handle, $shipping_product_id, $client);
		$client->CurrentInvoiceLine_SetQuantity(array(
		  'currentInvoiceLineHandle' => $handle,
		  'value' => 1
		));
	
	  }*/
	}
	
	public function woo_get_currentinvoice_orderline_from_economic(&$handle, SoapClient &$client){
	  logthis("woo_get_currentinvoice_orderline_from_economic id: " . $handle->Id . " numbner: " . $handle->Number);
	  $invoice_line = $client->CurrentInvoiceLine_GetData(array(
		'entityHandle' => $handle
	  ))->CurrentInvoiceLine_GetDataResult;
	
	  return $invoice_line;
	}
	
	
	/**
     * Get invoice lines to e-conomic 
     *
     * @access public
     * @param 
     * @return array log
     */
	public function woo_create_currentinvoice_orderline_at_economic($current_invoice_handle, $product_id, SoapClient &$client){
		$current_invoice_line_handle = $client->CurrentInvoiceLine_Create(array(
			'invoiceHandle' => array('Id' => $current_invoice_handle->Id)
		))->CurrentInvoiceLine_CreateResult;
		logthis("woo_create_currentinvoice_orderline_at_economic added line id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number . " product_id: " . $product_id);
		$product_handle = $client->Product_FindByNumber(array(
			'number' => $product_id
		))->Product_FindByNumberResult;
		$client->CurrentInvoiceLine_SetProduct(array(
			'currentInvoiceLineHandle' => $current_invoice_line_handle,
			'valueHandle' => $product_handle
		));
		$product = $client->Product_GetData(array(
			'entityHandle' => $product_handle
		))->Product_GetDataResult;
		$client->CurrentInvoiceLine_SetDescription(array(
			'currentInvoiceLineHandle' => $current_invoice_line_handle,
			'value' => $product->Name
		));
		$client->CurrentInvoiceLine_SetUnitNetPrice(array(
			'currentInvoiceLineHandle' => $current_invoice_line_handle,
			'value' => $product->SalesPrice
		));
		
		logthis("woo_create_currentinvoice_orderline_at_economic added product to line ");
		return $current_invoice_line_handle;
	}
	
	
	    /**
     * Save WooCommerce Order to e-conomic
     *
     * @access public
     * @param product oject, user object, Soap client object, reference order ID and refund flag.
     * @return bool
     */
	public function save_order_to_economic(WP_User $user, WC_Order $order, SoapClient &$client, $refund){
		global $wpdb;
		logthis("save_order_to_economic Getting debtor handle");
		$debtor_handle = $this->woo_get_debtor_handle_from_economic($user, $client);
		if (!($debtor_handle)) {
			logthis("save_order_to_economic debtor not found, can not create order");
			return false;
		}
		try {
		
			$order_handle = $this->woo_get_order_number_from_economic($client, $order->id, $debtor_handle);
			if (!$refund && isset($invoice_number)) {
				logthis("save_order_to_economic order already exists");
				return true;
			}
			
			$countries = new WC_Countries();
			
			$address = null;
			$city = null;
			$postalcode = null;
			$country = null;
			
			if (isset($order->shipping_address_1) || !empty($order->shipping_address_1)) {
				$formatted_state = $countries->states[$order->shipping_country][$order->shipping_state];
				$address = trim($order->shipping_address_1 . "\n" . $order->shipping_address_2 . "\n" . $formatted_state);
				$city = $order->shipping_city;
				$postalcode = $order->shipping_postcode;
				$country = $countries->countries[$order->shipping_country];
			} else {
				$formatted_state = $countries->states[$order->billing_country][$order->billing_state];
				$address = trim($order->billing_address_1 . "\n" . $order->billing_address_2 . "\n" . $formatted_state);
				$city = $order->billing_city;
				$postalcode = $order->billing_postcode;
				$country = $countries->countries[$order->billing_country];
			}
			
			
			logthis("save_order_to_economic Order_SetDeliveryAddress.");
			$client->Order_SetDeliveryAddress(array(
				'orderHandle' => $order_handle,
				'value' => $address
			));
			
			logthis("save_order_to_economic Order_SetDeliveryCity.");
			$client->Order_SetDeliveryCity(array(
				'orderHandle' => $order_handle,
				'value' => $city
			));
			
			logthis("save_order_to_economic Order_SetDeliveryPostalCode.");
			$client->Order_SetDeliveryPostalCode(array(
				'orderHandle' => $order_handle,
				'value' => $postalcode
			));
			
			logthis("save_order_to_economic Order_SetDeliveryCountry.");
			$client->Order_SetDeliveryCountry(array(
				'orderHandle' => $order_handle,
				'value' => $country
			));
			
			
			logthis("save_order_to_economic call woo_handle_order_lines_to_economic.");			
			$this->woo_handle_order_lines_to_economic($order, $order_handle, $client, $refund);
			
			
			
			//logthis("SELECT * FROM wce_orders WHERE order_id=".$order->id.": ".$wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id.";"));
		
			if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id.";")){
				$wpdb->update ("wce_orders", array('synced' => 1), array('order_id' => $order->id), array('%d'), array('%d'));
			}else{
				$wpdb->insert ("wce_orders", array('order_id' => $order->id, 'synced' => 1), array('%d', '%d'));
			}
			return true;
		} catch (Exception $exception) {
			logthis("save_order_to_economic could not save order: " . $exception->getMessage());
			$this->debug_client($client);
			logthis('Could not create invoice.');
			logthis($exception->getMessage());
			if($wpdb->query ("SELECT * FROM wce_orders WHERE order_id=".$order->id." AND synced=0;")){
				return false;
			}else{
				$wpdb->insert ("wce_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
				return false;
			}
		}
	}

	
	/**
     * Get or Create order number from economic
     *
     * @access public
     * @param User object, SOAP client, debtor_handle
     */	
	public function woo_get_order_number_from_economic(SoapClient &$client, $reference, &$debtor_handle){
		$economic_order = $client->Order_FindByOtherReference(array(
			'otherReference' => $reference
		))->Order_FindByOtherReferenceResult;
		
	
		if(isset($economic_order->Id) && !empty($economic_order->Id)){
			logthis("woo_get_order_number_from_economic orderId " . $economic_order->Id . " exists");
			return $economic_order;
		}else{
			logthis("woo_get_order_number_from_economic order doesn't exists, creating new order!");
			$economic_order = $client->Order_Create(array(
				'debtorHandle' => $debtor_handle
			))->Order_CreateResult;
			if(isset($economic_order->Id) && !empty($economic_order->Id)){
				logthis("woo_get_order_number_from_economic orderId " . $economic_order->Id . " created!");
				$client->Order_SetOtherReference(array(
					'orderHandle' => $economic_order,
					'value' => $reference
				));
				return $economic_order;
			}else{
				logthis("woo_get_order_number_from_economic creating new order failed!");
				return false;
			}
		}
	}

	
	
	/**
     * Get order lines handle
     *
     * @access public
     * @param Order object, Invoice handle object, SOAP client, refund bool
     * @return debtor_handle object
     */	
	public function woo_handle_order_lines_to_economic(WC_Order $order, $order_handle, SoapClient &$client, $refund){
	  logthis("woo_handle_order_lines_to_economic - get all lines");
	
	  foreach ($order->get_items() as $item) {
		$product = $order->get_product_from_item($item);
		//$line = $lines[$this->woo_get_product_sku($product)];
		$order_line_handle = null;
		$order_line_handle = $this->woo_create_orderline_handle_at_economic($order_handle, $this->woo_get_product_sku($product), $client);
	
		logthis("woo_handle_order_lines_to_economic updating qty on id: " . $order_line_handle->Id . " number: " . $order_line_handle->Number);
		$quantity = ($refund) ? $item['qty'] * -1 : $item['qty'];
		$client->OrderLine_SetQuantity(array(
		  'orderLineHandle' => $order_line_handle,
		  'value' => $quantity
		));
		logthis("woo_handle_order_lines_to_economic updated line");
	  }
	  
	    $shippingItem = reset($order->get_items('shipping'));
		//logthis($shippingItem['method_id']);
		if(isset($shippingItem['method_id'])){
			logthis("woo_handle_order_lines_to_economic adding Shipping line");
			$order_line_handle = null;
			$order_line_handle = $this->woo_create_orderline_handle_at_economic($order_handle, $shippingItem['method_id'], $client);
			logthis("woo_handle_order_lines_to_economic updating qty on id: " . $order_line_handle->Id . " number: " . $order_line_handle->Number);
			$quantity = ($refund) ? $item['qty'] * -1 : 1;
			$client->OrderLine_SetQuantity(array(
			'orderLineHandle' => $order_line_handle,
			'value' => $quantity
			));
			logthis("woo_handle_order_lines_to_economic updated shipping line");
		}
	}
	
	
	/**
     * Get order lines to e-conomic 
     *
     * @access public
     * @param 
     * @return array log
     */
	public function woo_create_orderline_handle_at_economic($order_handle, $product_id, SoapClient &$client){
		$orderline_handle = $client->OrderLine_Create(array(
			'orderHandle' => $order_handle
		))->OrderLine_CreateResult;
		logthis("woo_create_orderline_handle_at_economic added line id: " . $orderline_handle->Id . " number: " . $orderline_handle->Number . " product_id: " . $product_id);
		$product_handle = $client->Product_FindByNumber(array(
			'number' => $product_id
		))->Product_FindByNumberResult;
		$client->OrderLine_SetProduct(array(
			'orderLineHandle' => $orderline_handle,
			'valueHandle' => $product_handle
		));
		$product = $client->Product_GetData(array(
			'entityHandle' => $product_handle
		))->Product_GetDataResult;
		$client->OrderLine_SetDescription(array(
			'orderLineHandle' => $orderline_handle,
			'value' => $product->Name
		));
		$client->OrderLine_SetUnitNetPrice(array(
			'orderLineHandle' => $orderline_handle,
			'value' => $product->SalesPrice
		));
		
		logthis("woo_create_orderline_handle_at_economic added product to line ");
		return $orderline_handle;
	}
	
	
	/**
     * Sync WooCommerce orders to e-conomic 
     *
     * @access public
     * @param 
     * @return array log
     */
	public function sync_orders(){
		global $wpdb;
		$options = get_option('woocommerce_economic_general_settings');
		$client = $this->woo_economic_client();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => 'fail', 'msg' => 'Could not create e-conomic client, please try again later!' ));
			return $sync_log;
		}
		$orders = array();
		$sync_log = array();
		$sync_log[0] = true;
		logthis("sync_orders starting...");
        $unsynced_orders = $wpdb->get_results("SELECT * from wce_orders WHERE synced = 0");

		foreach ($unsynced_orders as $order){
			$orderId = $order->order_id;
			array_push($orders, new WC_Order($orderId));
		}
		
		if(!empty($orders)){
			foreach ($orders as $order) {
				logthis('sync_orders Order ID: ' . $order->id);
				$user = new WP_User($order->user_id);
				if($order->payment_method != 'economic-invoice'){
					if($this->activate_allsync != "on"){
						array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'order_id' => $order->id, 'msg' => __('Order not synced, because not an e-conomic order! Check "Aktivera alla beställningar synkning" to sync all order.', 'woocommerce-e-conomic-integration') ));
						continue; //Check if the payment is not e-conomic and all order sync is active, if not breaks this iterationa and continue with other orders.
					}
				}
				if($options['sync-order-invoice'] == 'invoice' || $order->payment_method == 'economic-invoice'){
					if($this->save_invoice_to_economic($user, $order, $client, $order->id)){
						array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'order_id' => $order->id, 'msg' => __('Order synced successfully' ), 'woocommerce-e-conomic-integration'));
					}else{
						$sync_log[0] = false;
						array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'order_id' => $order->id, 'msg' => __('Sync failed, please try again later!' , 'woocommerce-e-conomic-integration')));
					}
				}else{
					if($this->save_order_to_economic($user, $order, $client, $order->id)){
						array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'order_id' => $order->id, 'msg' => __('Order synced successfully', 'woocommerce-e-conomic-integration') ));
					}else{
						$sync_log[0] = false;
						array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'order_id' => $order->id, 'msg' => __('Sync failed, please try again later!' , 'woocommerce-e-conomic-integration')));
					}
				}
			}
		}else{
			$sync_log[0] = true;
			array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'order_id' => '', 'msg' => __('All orders were already synced!', 'woocommerce-e-conomic-integration') ));
		}
		
		$client->Disconnect();
		logthis("sync_orders ending...");
		return $sync_log;
	}
	
	
	 /**
     * Save WooCommerce Product to e-conomic
     *
     * @access public
     * @param product oject
     * @return bool
     */
	
	public function save_product_to_economic(WC_Product $product, SoapClient &$client){
		if(!$client){
			return false;
		}
		
		logthis("save_product_to_economic syncing product - sku: " . $product->sku . " title: " . $product->get_title() . " desc: " . $product->post->post_content);
		try	{
			$product_sku = $this->woo_get_product_sku($product);
			logthis("save_product_to_economic - trying to find product in economic with product number: ".$product_sku);
			
			// Find product by number
			logthis('Finding product by number: '.$product_sku);
			$product_handle = $client->Product_FindByNumber(array(
				'number' => $product_sku
			))->Product_FindByNumberResult;
			
			// Create product with name
			if (!$product_handle) {
				$productGroupHandle = $client->ProductGroup_FindByNumber(array(
					'number' => $this->product_group
				))->ProductGroup_FindByNumberResult;
				$product_handle = $client->Product_Create(array(
					'number' => $product_sku,
					'productGroupHandle' => $productGroupHandle,
					'name' => $product->get_title()
				))->Product_CreateResult;
				logthis("save_product_to_economic - product created:" . $product->get_title());
			}
			
			// Get product data
			$product_data = $client->Product_GetData(array(
				'entityHandle' => $product_handle
			))->Product_GetDataResult;
			
			logthis('--product_handle--');
			logthis($product_handle);
			
			//logthis($product_data->DepartmentHandle);
			//logthis($product_data->DistrubutionKeyHandle);
			
			// Update product data
			$client->Product_UpdateFromData(array(
			'data' => (object)array(
			'Handle' => $product_data->Handle,
			'Number' => $product_data->Number,
			'ProductGroupHandle' => $product_data->ProductGroupHandle,
			'Name' => $product->get_title(),
			'Description' => $this->woo_economic_product_content_trim($product->post->post_content, 255),
			'BarCode' => "",
			'SalesPrice' => (isset($product->price) && !empty($product->price) ? $product->price : 0.0),
			'CostPrice' => (isset($product_data->CostPrice) ? $product_data->CostPrice : 0.0),
			'RecommendedPrice' => $product_data->RecommendedPrice,
			'UnitHandle' => (object)array(
			'Number' => 1
			),
			'IsAccessible' => true,
			'Volume' => $product_data->Volume,
			//'DepartmentHandle' => isset($product_data->DepartmentHandle) ? $product_data->DepartmentHandle : '',
			//'DistributionKeyHandle' => isset($product_data->DistrubutionKeyHandle) ? $product_data->DistrubutionKeyHandle : '',
			'InStock' => $product_data->InStock,
			'OnOrder' => $product_data->OnOrder,
			'Ordered' => $product_data->Ordered,
			'Available' => $product_data->Available)))->Product_UpdateFromDataResult;
			logthis("save_product_to_economic - product updated : " . $product->get_title());
			return true;
		} catch (Exception $exception) {
			logthis("save_product_to_economic could not create product: " . $exception->getMessage());
			$this->debug_client($client);
			logthis($exception->getMessage);
			return false;
		}
	}
	
	
	 /**
	 * Removes tags and shortens the string to length
	 */
	 public function woo_economic_product_content_trim($str, $max_len)
	 {
	  logthis("woo_economic_product_content_trim '" . $str . "'");
	  $result = strip_tags($str);
	  if (strlen($result) > $max_len)
		$result = substr($result, 0, $max_len-1);
	
	  logthis("woo_economic_product_content_trim result: '" . $result . "'");
	
	  return $result;
	 }


	/**
     * Sync WooCommerce Products to e-conomic 
     *
     * @access public
     * @param 
     * @return array log
     */
	 
	 public function sync_products(){
		$client = $this->woo_economic_client();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => 'fail', 'msg' => 'Could not create e-conomic client, please try again later!' ));
			return $sync_log;
		}
		$products = array();
		$sync_log = array();
		$sync_log[0] = true;
		$args = array('post_type' => 'product', 'nopaging' => true);
		$product_query = new WP_Query($args);
		$posts = $product_query->get_posts();
		foreach ($posts as $post) {
			array_push($products, new WC_Product($post->ID));
		}
		logthis("sync_products starting...");
		foreach ($products as $product) {
			logthis('sync_products Product ID: ' . $product->id);
			logthis('sync_products saving product: ' . $product->get_title() . " sku: " . $product->sku);
			logthis('Product SKU: '. $product->sku );
			logthis('Product Title: '.$product->get_title());
			$title = $product->get_title();
			if (isset($product->sku) && !empty($product->sku) && isset($title) && !empty($title)) {
				if($this->save_product_to_economic($product, $client)){
					array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product synced successfully', 'woocommerce-e-conomic-integration') ));
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product not synced, please try again!', 'woocommerce-e-conomic-integration') ));
				}
			} else {
				logthis("Could not sync product: '". $product->get_title() ."' and id: '".$product->id."' to e-conomic. Please update it with:");
				if (!isset($product->sku) || empty($product->sku)){
				  logthis("SKU");
				  $sync_log[0] = false;
				  array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'sku' => '', 'name' => $product->get_title(), 'msg' => __('Product not synced, SKU is empty!', 'woocommerce-e-conomic-integration') ));
				}
				if (!isset($title) || empty($title)){
				  logthis("Title");
				  $sync_log[0] = false;
				  array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'sku' => $product->sku, 'name' => '', 'msg' => __('Product not synced, product title is empty!', 'woocommerce-e-conomic-integration') ));
				}
			}
		}
		
		$client->Disconnect();
		logthis("sync_products ending...");
		return $sync_log;
	 }
	 
	 /**
     * Save WooCommerce Product to e-conomic
     *
     * @access public
     * @param product oject
     * @return bool
     */
	 
	 public function save_customer_to_economic(WP_User $user, SoapClient &$client){
	  logthis("save_customer_to_economic creating client");
	  global $wpdb;	
	  try {
		$debtorHandle = $this->woo_get_debtor_handle_from_economic($user, $client);
		if (!isset($debtorHandle)) { // The debtor doesn't exist - lets create it
		  $debtor_grouphandle_meta = $this->customer_group;
		  logthis("save_customer_to_economic debtor group: " . $debtor_grouphandle_meta);
		  //logthis($user);
		  logthis("save_customer_to_economic name: " . $user->get('first_name') != '' ? $user->get('first_name') : $user->get('billing_first_name') . " " . $user->get('last_name') != '' ? $user->get('last_name') : $user->get('billing_last_name'));
		  logthis("save_customer_to_economic billing_comnpany: " . $user->get('billing_company'));

		  $debtor_grouphandle = $client->DebtorGroup_FindByNumber(array(
			'number' => $debtor_grouphandle_meta
		  ))->DebtorGroup_FindByNumberResult;
		  $debtorHandle = $client->Debtor_Create(array(
			'nubmer' => $this->woo_get_customer_id($user),
			'debtorGroupHandle' => $debtor_grouphandle,
			'name' => $user->get('billing_company')!='' ? $user->get('billing_company') : $user->get('last_name') != '' ? $user->get('last_name') : $user->get('billing_last_name'),
			'vatZone' => 'HomeCountry' // todo remember to make switch over eu countries, your own and international.
		  ))->Debtor_CreateResult;
		  update_user_meta($user->ID, 'debtor_number', $debtorHandle->Number);
		}
		if (isset($debtorHandle)) {
			$debtor_delivery_location_handle = $this->woo_get_debtor_delivery_location_handles_from_economic($user, $client, $debtorHandle);
			logthis("save_customer_to_economic woo_get_debtor_handle_from_economic handle returned: " . $debtorHandle->Number);
			
			foreach ($this->user_fields as $meta_key) {
				$this->woo_save_customer_meta_data_to_economic($user, $client, $meta_key, $user->get($meta_key), $debtorHandle, $debtor_delivery_location_handle);
			}
			
			if($wpdb->query ("SELECT * FROM wce_customers WHERE user_id=".$user->ID.";")){
				$wpdb->update ("wce_customers", array('synced' => 1, 'customer_number' => $user->get('debtor_number')), array('user_id' => $user->ID), array('%d', '%d'), array('%d'));
			}else{
				$wpdb->insert ("wce_customers", array('user_id' => $user->ID, 'customer_number' => $user->get('debtor_number'), 'synced' => 1), array('%d', '%s', '%d'));
			}
			return true;
		}else{
			logthis("save_customer_to_economic debtor not found.");
			return false;
		}
	  } catch (Exception $exception) {
		logthis("save_customer_to_economic could not save user to e-conomic: " . $exception->getMessage());
		$this->debug_client($client);
		logthis("Could not create user.");
		logthis($exception->getMessage());
		if($wpdb->query ("SELECT * FROM wce_customers WHERE user_id=".$user->id." AND synced=0;")){
			return false;
		}else{
			$wpdb->insert ("wce_customers", array('user_id' => $user->ID, 'customer_number' => '0', 'synced' => 0), array('%d', '%s', '%d'));
			return false;
		}
	  }
	}
	
	/**
     * Get Customer id concatenated with customer offest.
     *
     * @access public
     * @param user object
     * @return customer id concatenated with customer offest string.
     */
	public function woo_get_customer_id(WP_User $user){
	  $customer_id = $user->ID;
	  logthis("woo_get_customer_id id: " . $customer_id);
	  return $customer_id;
	}
	
	/**
     * Save customer meta data to economic
     *
     * @access public
     * @param user object, $meta_key, $meta_value
     * @return void
     */
	public function woo_save_customer_meta_data_to_economic(WP_User $user, SoapClient &$client, $meta_key, $meta_value, $debtor_handle, $debtor_delivery_location_handle){
	  logthis("woo_save_customer_meta_data_to_economic updating client");
	  //logthis($debtor_handle);
	  //logthis($debtor_delivery_location_handle);
	  if (!isset($debtor_handle)) {
		logthis("woo_save_customer_meta_data_to_economic debtor not found, can not update meta");
		return;
	  }
	  try {
	
		if ($meta_key == 'billing_phone') {
		  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
		  $client->Debtor_SetTelephoneAndFaxNumber(array(
			'debtorHandle' => $debtor_handle,
			'value' => $meta_value
		  ));
		} elseif ($meta_key == 'billing_email') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetEmail(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_country') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $countries = new WC_Countries();
			  $country = $countries->countries[$meta_value];
			  logthis("woo_save_customer_meta_data_to_economic country: " . $country);
			  $client->Debtor_SetCountry(array(
				'debtorHandle' => $debtor_handle,
				'value' => $country
		  ));
		} elseif ($meta_key == 'billing_address_1' || $meta_key == 'billing_address_2' || $meta_key == 'billing_state') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $adr1 = ($meta_key == 'billing_address_1') ? $meta_value : $user->get('billing_address_1');
			  $adr2 = ($meta_key == 'billing_address_2') ? $meta_value : $user->get('billing_address_2');
			  $state = ($meta_key == 'billing_state') ? $meta_value : $user->get('billing_state');
			  $billing_country = $user->get('billing_country');
			  $countries = new WC_Countries();		
			  $formatted_state = (isset($state)) ? $countries->states[$billing_country][$state] : "";
			  $formatted_adr = trim($adr1."\n".$adr2."\n".$formatted_state);
			  logthis("woo_save_customer_meta_data_to_economic adr1: " . $adr1 . " adr2: " . $adr2 . " state " . $formatted_state);
			  logthis("woo_save_customer_meta_data_to_economic formatted_adr: " . $formatted_adr);
			  $client->Debtor_SetAddress(array(
				'debtorHandle' => $debtor_handle,
				'value' => $formatted_adr
			  ));
	
		} elseif ($meta_key == 'billing_postcode') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetPostalCode(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_city') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetCity(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_company' && $meta_value != '') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetName(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif($meta_key == 'billing_first_name' || $meta_key == 'billing_last_name') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $first = ($meta_key == 'billing_first_name') ? $meta_value : $user->get('billing_first_name');
			  $last = ($meta_key == 'billing_last_name') ? $meta_value : $user->get('billing_last_name');
			  $name = $first . " " . $last;
			  $debtor_contact_handle = $client->DebtorContact_Create(array(
				'debtorHandle' => $debtor_handle,
				'name' => $name))->DebtorContact_CreateResult;
			  $client->Debtor_SetAttention(array(
				'debtorHandle' => $debtor_handle,
				'valueHandle' => $debtor_contact_handle
			  ));
	
		} elseif ($meta_key == 'shipping_country') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $countries = new WC_Countries();
			  $country = $countries->countries[$meta_value];
			  logthis("woo_save_customer_meta_data_to_economic country: " . $country);
			  $client->DeliveryLocation_SetCountry (array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $country
			  ));
		} elseif ($meta_key == 'shipping_postcode') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->DeliveryLocation_SetPostalCode(array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'shipping_city') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->DeliveryLocation_SetCity (array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $meta_value
			  ));
	
		}
		elseif($meta_key == 'shipping_address_1' || $meta_key == 'shipping_address_2' || $meta_key == 'shipping_state') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $adr1 = ($meta_key == 'shipping_address_1') ? $meta_value : $user->get('shipping_address_1');
			  $adr2 = ($meta_key == 'shipping_address_2') ? $meta_value : $user->get('shipping_address_2');
			  $state = ($meta_key == 'shipping_state') ? $meta_value : $user->get('shipping_state');
			  $shipping_country = $user->get('shipping_country');
			  $countries = new WC_Countries();
			  $formatted_state = (isset($state)) ? $countries->states[$shipping_country][$state] : "";
			  $formatted_adr = trim("$adr1\n$adr2\n$formatted_state");
			  logthis("woo_save_customer_meta_data_to_economic adr1: " . $adr1 . " adr2 " . $adr2 . " state " . $formatted_state);
			  //logthis("debtor_delivery_location_handle:");
			  //logthis($debtor_delivery_location_handle);
			  $client->DeliveryLocation_SetAddress(array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $formatted_adr
			  ));
		} else{
			logthis("woo_save_customer_meta_data_to_economic unknown meta_key :".$meta_key." meta_value: ".$meta_value);
		}
		return true;
	  } catch (Exception $exception) {
		logthis("woo_save_customer_meta_data_to_economic could not update debtor: " . $exception->getMessage());
		$this->debug_client($client);
		logthis("Could not update debtor.");
		logthis($exception->getMessage());
		return false;
	  }
	}
	
	/**
     * Sync WooCommerce users to e-conomic 
     *
     * @access public
     * @param 
     * @return array log
     */
	public function sync_contacts(){
		global $wpdb;
		$client = $this->woo_economic_client();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'msg' => __('Could not create e-conomic client, please try again later!', 'woocommerce-e-conomic-integration') ));
			return $sync_log;
		}
		$users = array();
		$sync_log = array();
		$sync_log[0] = true;
		logthis("sync_contacts starting...");
		$args = array(
			'role' => 'customer',
		);
		$customers = get_users( $args );
		foreach ($customers as $customer){
			if($customer->get('debtor_number') == ''){
				array_push($users, $customer);
			}
		}
        $unsynced_users = $wpdb->get_results("SELECT * from wce_customers WHERE synced = 0");
		foreach ($unsynced_users as $user){
			array_push($users, new WP_User($user->user_id));
		}
		
		//logthis($users);
		if(!empty($users)){
			foreach ($users as $user) {
				logthis('sync_contacts User ID: ' . $user->ID);
				if($this->save_customer_to_economic($user, $client)){
					array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'user_id' => $user->ID, 'msg' => __('Customer synced successfully', 'woocommerce-e-conomic-integration') ));
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'user_id' => $user->ID, 'msg' => __('Sync failed, please try again later!', 'woocommerce-e-conomic-integration') ));
				}
			}
		}else{
			$sync_log[0] = true;
			array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'user_id' => '', 'msg' => __('All customers were already synced!', 'woocommerce-e-conomic-integration') ));
		}

		$client->Disconnect();
		logthis("sync_contacts ending...");
		return $sync_log;
	}
	
	
	/**
     * Save WooCommerce Shipping to e-conomic
     *
     * @access public
     * @param shipping settings array
     * @return bool
     */
	
	public function save_shipping_to_economic($shippingMethodObject, SoapClient &$client){
		if(!$client){
			return false;
		}
		
		logthis("save_shipping_to_economic syncing shipping ID - sku: " . $shippingMethodObject->id . " title: " . $shippingMethodObject->settings['title']);
		try	{
			$shippingID = $shippingMethodObject->id;
			logthis("save_shipping_to_economic - trying to find shipping in economic");
			
			// Find product by number
			$product_handle = $client->Product_FindByNumber(array(
			'number' => $shippingID))->Product_FindByNumberResult;
			
			// Create product with name
			if (!$product_handle) {
				$productGroupHandle = $client->ProductGroup_FindByNumber(array(
				'number' => $this->product_group))->ProductGroup_FindByNumberResult;
				$product_handle = $client->Product_Create(array(
				'number' => $shippingID,
				'productGroupHandle' => $productGroupHandle,
				'name' => $shippingMethodObject->settings['title']))->Product_CreateResult;
				logthis("save_shipping_to_economic - shipping created:" . $shippingMethodObject->settings['title']);
			}
			
			// Get product data
			$product_data = $client->Product_GetData(array(
			'entityHandle' => $product_handle))->Product_GetDataResult;
			
			if(isset($shippingMethodObject->settings['additional_costs']) && $shippingMethodObject->settings['additional_costs'] > 0){
				$shippingCost = $shippingMethodObject->settings['cost_per_order'] + $shippingMethodObject->settings['cost'] + $shippingMethodObject->settings['additional_costs'];
				if($shippingMethodObject->settings['fee'] >= $shippingMethodObject->settings['minimum_fee']){
					$shippingCost = $shippingCost + $shippingMethodObject->settings['fee'];
				}else{
					$shippingCost = $shippingCost + $shippingMethodObject->settings['minimum_fee'];
				}
			}else{
				$shippingCost = $shippingMethodObject->settings['cost_per_order'] + $shippingMethodObject->settings['cost'];
				if($shippingMethodObject->settings['fee'] >= $shippingMethodObject->settings['minimum_fee']){
					$shippingCost = $shippingCost + $shippingMethodObject->settings['fee'];
				}else{
					$shippingCost = $shippingCost + $shippingMethodObject->settings['minimum_fee'];
				}
			}
			
			
			// Update product data
			$client->Product_UpdateFromData(array(
			'data' => (object)array(
			'Handle' => $product_data->Handle,
			'Number' => $product_data->Number,
			'ProductGroupHandle' => $product_data->ProductGroupHandle,
			'Name' => $shippingMethodObject->settings['title'],
			'Description' => $shippingMethodObject->settings['title'],
			'BarCode' => "",
			'SalesPrice' => $shippingCost > 0 ? $shippingCost : 0.0,
			'CostPrice' => (isset($product_data->CostPrice) ? $product_data->CostPrice : 0.0),
			'RecommendedPrice' => $product_data->RecommendedPrice,
			'UnitHandle' => (object)array(
			'Number' => 1
			),
			'IsAccessible' => true,
			'Volume' => $product_data->Volume,
			//'DepartmentHandle' => $product_data->DepartmentHandle,
			//'DistributionKeyHandle' => $product_data->DistrubutionKeyHandle,
			'InStock' => $product_data->InStock,
			'OnOrder' => $product_data->OnOrder,
			'Ordered' => $product_data->Ordered,
			'Available' => $product_data->Available)))->Product_UpdateFromDataResult;
			logthis("save_shipping_to_economic - product updated : " . $shippingMethodObject->settings['title']);
			return true;
		} catch (Exception $exception) {
			logthis("save_shipping_to_economic could not create product: " . $exception->getMessage());
			$this->debug_client($client);
			logthis($exception->getMessage);
			return false;
		}
	}
	
	
	/**
     * Sync WooCommerce shipping as products to e-conomic 
     *
     * @access public
     * @param 
     * @return array log
     */
	public function sync_shippings(){
		$client = $this->woo_economic_client();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'msg' => __('Could not create e-conomic client, please try again later!', 'woocommerce-e-conomic-integration') ));
			return $sync_log;
		}
		$sync_log = array();
		$sync_log[0] = true;
		$shipping = new WC_Shipping();
		$shippingMethods = $shipping->load_shipping_methods();
		logthis("sync_shippings starting...");
		//logthis($shippingMethods);
		foreach ($shippingMethods as $shippingMethod => $shippingMethodObject) {
			logthis('Shipping ID: '. $shippingMethodObject->id );
			logthis('Shipping Title: '. $shippingMethodObject->settings['title']);
			$title = $shippingMethodObject->settings['title'];
			if($this->save_shipping_to_economic($shippingMethodObject, $client)){
				array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $shippingMethodObject->id, 'name' => $shippingMethodObject->settings['title'], 'msg' => __('Shipping synced successfully', 'woocommerce-e-conomic-integration') ));
			}else{
				$sync_log[0] = false;
				array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'sku' => $shippingMethodObject->id, 'name' => $shippingMethodObject->settings['title'], 'msg' => __('Shipping not synced, please try again!', 'woocommerce-e-conomic-integration') ));
			}
		}
		
		$client->Disconnect();
		logthis("sync_shippings ending...");
		return $sync_log;
	}
	
	
	/**
     * Send inovice of an order from e-conomic to customers
     *
     * @access public
     * @param user object, order object, e-conomic client
     * @return boolean
     */
	public function send_invoice_economic($user, $order, SoapClient &$client){
		try{
			$current_invoice_handle = $client->CurrentInvoice_FindByOtherReference(array(
				'otherReference' => $order->id
			))->CurrentInvoice_FindByOtherReferenceResult;
			
			logthis('send_invoice_economic CurrentInvoiceHandleId:'. $current_invoice_handle->CurrentInvoiceHandle->Id);
			logthis($current_invoice_handle);
			
			logthis('send_invoice_economic book invoice');
			
			$invoice = $client->CurrentInvoice_Book(array(
				'currentInvoiceHandle' => $current_invoice_handle->CurrentInvoiceHandle
			))->CurrentInvoice_BookResult;
			
			logthis('send_invoice_economic invoice: '. $invoice->Number);
			logthis($invoice);
			
			$pdf_invoice = $client->Invoice_GetPdf(array(
				'invoiceHandle' => $invoice
			))->Invoice_GetPdfResult;
			
			//logthis('send_invoice_economic pdf_base64_data:');
			//logthis($pdf_invoice);
			
			logthis('send_invoice_economic Creating PDF invoice');
			$filename = 'ord_'.$order->id.'-inv_'.$invoice->Number.'.pdf';
			$path = dirname(__FILE__).'/invoices/';
			$file = $path.$filename;
			if(!file_exists($file)){
				$fileobject = fopen($file, 'w');
			}
			fwrite ($fileobject, $pdf_invoice);
			fclose ($fileobject);
			logthis('send_invoice_economic Invoice '.$file.' is created');
			
			$to = $order->billing_email;
			$orderDate = explode(' ', $order->order_date);
			$subject = get_bloginfo( $name ).' - Invoice no. '.$invoice->Number.' - '.$orderDate[0];
			$body = '';
			/*$random_hash = md5(date('r', time())); 
			//$headers = 'Content-Type: text/html; charset=UTF-8';
			//$headers. = 'From: '.get_bloginfo( 'name' ).' <'.get_bloginfo( 'admin_email' ).'>';
			
			$headers = "MIME-Version: 1.0" . "\r\n";
			//$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
			$headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\""; 
			$headers .= "From: ".get_bloginfo( 'name' )." <"..">"."\r\n";
		
			//logthis('To: '.$to.'/n Subject: '.$subject.'/n Headers: '.$headers);*/
			
			return $this->mail_attachment($filename, $path, $to, get_bloginfo( 'admin_email' ), get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ), $subject, $body );
			
		}catch (Exception $exception) {
			logthis($exception->getMessage);
			$this->debug_client($client);
			return false;
		}
	}
	
	public function mail_attachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
		$file = $path.$filename;
		$file_size = filesize($file);
		//logthis('file_size: '.$file_size);
		$handle = fopen($file, "r");
		$content = fread($handle, $file_size);
		//logthis('content: '.$content);
		fclose($handle);
		$content = chunk_split(base64_encode($content));
		$uid = md5(uniqid(time()));
		$name = basename($file);
		$header = "From: ".$from_name." <".$from_mail.">\r\n";
		$header .= "Reply-To: ".$replyto."\r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
		$header .= "This is a multi-part message in MIME format.\r\n";
		$header .= "--".$uid."\r\n";
		$header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
		$header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
		$header .= $message."\r\n\r\n";
		$header .= "--".$uid."\r\n";
		$header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use different content types here
		$header .= "Content-Transfer-Encoding: base64\r\n";
		$header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
		$header .= $content."\r\n\r\n";
		$header .= "--".$uid."--";
		return mail($mailto, $subject, "", $header);
	}

}