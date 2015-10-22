<?php
//php.ini overriding necessary for communicating with the SOAP server.
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
error_reporting(0);
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
	
	/** @public string yes/no */
    public $activate_oldordersync;
	
	public $product_sync;
	
	
	/** @public array including all the customer meta fiedls that are snyned */
	public $user_fields = array(
	  'billing_phone',
	  'billing_email',
	  'billing_country',
	  'billing_address_1',
	  //'billing_address_2',
	  //'billing_state',
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
	  //'shipping_address_2',
	  //'shipping_state',
	  'shipping_postcode',
	  'shipping_city',
	  'shipping_country',
	  'shipping_company',
	  'shipping_last_name',
	  'shipping_first_name'
	);
	
	public $eu = array(
		'BE' => 'Belgium',
		'BG' => 'Bulgaria',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'GE' => 'Germany',
		'EE' => 'Estonia',
		'IE' => 'Republic of Ireland',
		'EL' => 'Greece',
		'ES' => 'Spain',
		'FR' => 'France',
		'HR' => 'Croatia',
		'IT' => 'Italy',
		'CY' => 'Cyprus',
		'LV' => 'Latvia',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'HU' => 'Hungary',
		'MT' => 'Malta',
		'NL' => 'Netherlands',
		'AT' => 'Austria',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'RO' => 'Romania',
		'SI' => 'Slovenia',
		'SK' => 'Slovakia',
		'FI' => 'Finland',
		'SE' => 'Sweden',
		'GB' => 'United Kingdom'
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
		$this->appToken = '15MjebGLGLPv4_I90Wy8EqzcXwThPmrY5iRNlG0H3_w1';
		
		$this->product_group = isset($options['product-group'])? $options['product-group']: '';
		$this->product_offset = isset($options['product-prefix'])? $options['product-prefix']: '';
		$this->customer_group = isset($options['customer-group'])? $options['customer-group']: '';
		$this->activate_allsync = isset($options['activate-allsync'])? $options['activate-allsync'] : '';
		$this->activate_oldordersync = isset($options['activate-oldordersync'])? $options['activate-oldordersync'] : '';
		$this->product_sync = isset($options['product-sync'])? $options['product-sync'] : '';
		$this->sync_order_invoice = isset($options['sync-order-invoice'])? $options['sync-order-invoice'] : '';
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
	
	  //logthis("woo_economic_client loaded token: " . $this->token . " appToken: " . $this->appToken);
	  if (!$this->token || !$this->appToken)
		die("e-conomic Access Token not defined!");
		
	  //logthis("woo_economic_client - options are OK!");
	  //logthis("woo_economic_client - creating client...");
	  	  
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
        //$whmcsurl = 'http://176.10.250.47/whmcs/'; $whmcsurlsock = '176.10.250.47/whmcs';
        $whmcsurl = 'http://whmcs.onlineforce.net/'; $whmcsurlsock = 'whmcs.onlineforce.net';
        // Must match what is specified in the MD5 Hash Verification field
        // of the licensing product that will be used with this check.
        //$licensing_secret_key = 'itservice';
		$licensing_secret_key = 'ak4762';
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
		$host= gethostname();
		//$usersip = gethostbyname($host);
        $usersip = gethostbyname($host) ? gethostbyname($host) : $_SERVER['SERVER_ADDR'];
        //$usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
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
                $fp = fsockopen($whmcsurlsock, 80, $errno, $errstr, 5);
				//logthis($errstr.':'.$errno);
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
     * @param  Soap client object, user object or NULL, order object or NULL and refund flag.
     * @return bool
     */
	public function save_invoice_to_economic(SoapClient &$client, WP_User $user = NULL, WC_Order $order = NULL, $refund = NULL){
		global $wpdb;
		logthis("save_invoice_to_economic Getting debtor handle");
		
		$debtor_handle = $this->woo_get_debtor_handle_from_economic($client, $user, $order);
		
		if (!($debtor_handle)) {
			logthis("save_invoice_to_economic debtor not found, can not create invoice");
			return false;
		}
		try {
		
			$invoice_number = $this->woo_get_invoice_number_from_economic($client, $order->id);
			if ($refund && isset($invoice_number)) {
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
	public function woo_get_debtor_handle_from_economic(SoapClient &$client, WP_User $user = NULL, WC_Order $order = NULL){
		try {
			if(is_object($user)){
				$debtorNumber = $user->get('debtor_number');
				logthis("woo_get_debtor_handle_from_economic trying to load : " . $debtorNumber);
				if (!isset($debtorNumber) || empty($debtorNumber)) {
					logthis("woo_get_debtor_handle_from_economic no handle found");
					$debtor_handle = array();
				}else{
					$debtor_handle = $client->Debtor_FindByNumber(array(
						'number' => $debtorNumber
					))->Debtor_FindByNumberResult;
				}
			}else{
				if(is_object($order)){
					logthis("woo_get_debtor_handle_from_economic user not defined, guest user suspected, fetching debtorNumber by order email: ".$order->billing_email);
					$debtor_handles = $client->Debtor_FindByEmail(array(
						'email' => $order->billing_email
					))->Debtor_FindByEmailResult;
					$debtor_handle = $debtor_handles->DebtorHandle;
					logthis($debtor_handle);
				}
				else{
					logthis("woo_get_debtor_handle_from_economic user not defined, guest user suspected, fetching debtorNumber by email: ".$user);
					$debtor_handles = $client->Debtor_FindByEmail(array(
						'email' => $user
					))->Debtor_FindByEmailResult;
					$debtor_handle = $debtor_handles->DebtorHandle;
				}
			}	
			
			$debtor_handle_array = (array) $debtor_handle;	
			
			$tax_based_on = get_option('woocommerce_tax_based_on');
			$vatZone = 'HomeCountry';
			
			if($tax_based_on == 'billing'){
				$vatZone = $this->woo_get_debtor_vat_zone('billing', $user, $order);
			}elseif($tax_based_on == 'shipping'){
				$vatZone = $this->woo_get_debtor_vat_zone('shipping', $user, $order);
			}else{
				$vatZone = 'HomeCountry';
			}
			
			if (!empty($debtor_handle_array)) {
				logthis("woo_get_debtor_handle_from_economic debtor found for user.");
				//logthis($user != NULL? $user->ID : $order->billing_email);
				logthis($debtor_handle);
				$client->Debtor_SetVatZone(array(
						//'number' => $user->ID,
						'debtorHandle' => $debtor_handle,
						'value' => $vatZone
					)
				);
			}
			else {
				// The debtor doesn't exist - lets create it
				logthis("woo_get_debtor_handle_from_economic debtor doesn't exit, creating debtor");
				$debtor_grouphandle_meta = $this->customer_group;
				logthis("woo_get_debtor_handle_from_economic debtor group: " . $debtor_grouphandle_meta);
				//logthis($user);
				
				if($user != NULL){
					$name = $user->get('last_name').' '.$user->get('first_name');
					$billing_company = $user->get('billing_company');
					logthis("woo_get_debtor_handle_from_economic name: " . $name);
					logthis("woo_get_debtor_handle_from_economic billing_comnpany: " . $billing_company);
				
					$debtor_grouphandle = $client->DebtorGroup_FindByNumber(array(
						'number' => $debtor_grouphandle_meta
					))->DebtorGroup_FindByNumberResult;					
					
					$debtor_handle = $client->Debtor_Create(array(
						//'number' => $user->ID,
						'debtorGroupHandle' => $debtor_grouphandle,
						'name' => $name,
						'vatZone' => $vatZone
					))->Debtor_CreateResult;
					update_user_meta($user->ID, 'debtor_number', $debtor_handle->Number);
					logthis("woo_get_debtor_handle_from_economic debtor created using user object: " . $name);
				}else{
					logthis("woo_get_debtor_handle_from_economic name: " . $order->billing_first_name. " " . $order->billing_last_name);
					logthis("woo_get_debtor_handle_from_economic billing_comnpany: " . $order->billing_company);
				
					$debtor_grouphandle = $client->DebtorGroup_FindByNumber(array(
						'number' => $debtor_grouphandle_meta
					))->DebtorGroup_FindByNumberResult;
				
					$debtor_number = mt_rand( 9999, 99999 );
				
					$debtor_handle = $client->Debtor_Create(array(
						//'number' => $debtor_number,
						'debtorGroupHandle' => $debtor_grouphandle,
						'name' => $order->last_name.' '.$order->first_name,
						'vatZone' => $vatZone
					))->Debtor_CreateResult;
					logthis("woo_get_debtor_handle_from_economic debtor created using order object: " . $order->billing_email);
				}
				//logthis("woo_get_debtor_handle_from_economic debtor created for user->id " . $user != NULL? $user->ID : $order->billing_email);
			}
			
			if(is_array($debtor_handle)){
				$debtor_handle = $debtor_handle[0];
			}
			return $debtor_handle;
		}catch (Exception $exception) {
			logthis("woo_get_debtor_handle_from_economic could get or create debtor handle: " . $exception->getMessage());
			//$wce_api->debug_client($client);
			return null;
		}
	}
	
	/**
     * Get debtor debtor vat Zone from WooCommerce user object or order object.
     *
     * @access public
     * @param Type of address billing or shipping, WP user object and WC order object
     * @return vatZone string.
     */
	public function woo_get_debtor_vat_zone($type, WP_User $user = NULL, WC_Order $order = NULL){
		$default_country = get_option('woocommerce_default_country');
		$address = $type.'_country';
		logthis('woo_get_debtor_vat_zone running...');
		//logthis($order->$address.' == '.$default_country);
		if(is_object($order)){
			if($order->$address == $default_country){
				return 'HomeCountry';
			}elseif(isset($this->eu[$order->$address])){
				return 'EU';
			}else{
				return 'Abroad';
			}
		}
		if(is_object($user)){
			$userCountry = get_user_meta($user->ID, $address, true);
			if($userCountry == $default_country){
				return 'HomeCountry';
			}elseif(isset($this->eu[$userCountry])){
				return 'EU';
			}else{
				return 'Abroad';
			}
		}
	}
	
	/**
     * Get debtor delivery locations handle from economic
     *
     * @access public
     * @param User object, SOAP client
     * @return debtor_delivery_location_handles object
     */
	public function woo_get_debtor_delivery_location_handles_from_economic(SoapClient &$client, $debtor_handle){
		
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
	public function save_order_to_economic(SoapClient &$client, WP_User $user = NULL, WC_Order $order = NULL, $refund = NULL){
		global $wpdb;
		logthis("save_order_to_economic Getting debtor handle");
		$debtor_handle = $this->woo_get_debtor_handle_from_economic($client, $user, $order);
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
			
			$orderLines = $client->Order_GetLines(array(
				'orderHandle' => $order_handle,
				'value' => $country
			))->Order_GetLinesResult;
			
			//logthis($orderLines);
			
			if(isset($orderLines->OrderLineHandle)){
				if(is_array($orderLines->OrderLineHandle)){
					foreach($orderLines->OrderLineHandle as $orderLine){
						$client->OrderLine_Delete(array(
							'orderLineHandle' => $orderLine,
						));
					}
				}else{
					$client->OrderLine_Delete(array(
						'orderLineHandle' => $orderLines->OrderLineHandle,
					));
				}
			}
			
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
		
		//logthis($economic_order);
	
		if(isset($economic_order->OrderHandle->Id) && !empty($economic_order->OrderHandle->Id)){
			logthis("woo_get_order_number_from_economic orderId " . $economic_order->Id . " exists");
			return $economic_order->OrderHandle;
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
		
		$product_handle = $client->Product_FindByNumber(array(
			'number' => $product_id
		))->Product_FindByNumberResult;
		
		$orderline_handle = $client->OrderLine_Create(array(
			'orderHandle' => $order_handle
		))->OrderLine_CreateResult;
		
		logthis("woo_create_orderline_handle_at_economic added line id: " . $orderline_handle->Id . " number: " . $orderline_handle->Number . " product_id: " . $product_id);
		
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
		
		if($this->activate_oldordersync == "on"){
			$all_unsynced_orders = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts WHERE ID NOT IN (SELECT order_id FROM wce_orders) AND post_type='shop_order' AND post_status != 'trash' AND post_status != 'wc-failed'");
			foreach ($all_unsynced_orders as $order){
				$orderId = $order->ID;
				array_push($orders, new WC_Order($orderId));
			}
		}
		
		if(!empty($orders)){
			foreach ($orders as $order) {
				logthis('sync_orders Order ID: ' . $order->id);
				if($order->customer_user != 0){
					$user = new WP_User($order->customer_user);
				}else{
					$user = NULL;
				}
				$this->save_customer_to_economic($client, $user, $order);
				if($order->customer_user != 0){
					$user = new WP_User($order->customer_user);
				}else{
					$user = NULL;
				}
				if($order->payment_method != 'economic-invoice'){
					if($this->activate_allsync != "on"){
						array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'order_id' => $order->id, 'msg' => __('Order not synced, because not an e-conomic order! Check "Aktivera alla beställningar synkning" to sync all order.', 'woocommerce-e-conomic-integration') ));
						continue; //Check if the payment is not e-conomic and all order sync is active, if not breaks this iteration and continue with other orders.
					}
				}
				if($this->sync_order_invoice == 'invoice' || $order->payment_method == 'economic-invoice'){
					if($this->save_invoice_to_economic($client, $user, $order, false)){
						array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'order_id' => $order->id, 'msg' => __('Order synced successfully' ), 'woocommerce-e-conomic-integration'));
						if($order->payment_method == 'economic-invoice'){
							if($this->send_invoice_economic($client, $order)){
								logthis("sync_orders invoice for order: " . $order_id . " is sent to customer.");
							}else{
								logthis("sync_orders invoice for order: " . $order_id . " sending failed!");
							}
						}
					}else{
						$sync_log[0] = false;
						array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'order_id' => $order->id, 'msg' => __('Sync failed, please try again later!' , 'woocommerce-e-conomic-integration')));
					}
				}else{
					if($this->save_order_to_economic($client, $user, $order, false)){
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
		
		$options = get_option('woocommerce_economic_general_settings');
		
		
		
		logthis("save_product_to_economic syncing product - sku: " . $product->sku . " title: " . $product->get_title());
		try	{
			$product_sku = $this->woo_get_product_sku($product);
			//logthis("save_product_to_economic - trying to find product in economic with product number: ".$product_sku);
			
			// Find product by number
			logthis('Finding product by number: '.$product_sku);
			$product_handle = $client->Product_FindByNumber(array(
				'number' => $product_sku
			))->Product_FindByNumberResult;
			
			logthis('--product_handle--');
			logthis($product_handle);
			
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
				logthis($product_handle);
				logthis("save_product_to_economic - product created:" . $product->get_title());
			}
			
			// Get product data
			$product_data = $client->Product_GetData(array(
				'entityHandle' => $product_handle
			))->Product_GetDataResult;
			
			
			//logthis($product_data);
			//return true;

			//logthis($product_data->DepartmentHandle);
			//logthis($product_data->DistrubutionKeyHandle);
			if($this->product_sync != "on"){
				logthis("Product sync exiting, because product sync is not activated");
				
				//Update InStock from e-conomic to woocommerce
				if($product->managing_stock()){
					($product_data->InStock !=0 || $product_data->InStock =='') ? $product->set_stock($product_data->InStock) : logthis('Product stock not updated.');
					logthis('Product: '.$product->get_title().' Stock updated to '.$product_data->InStock);
				}else{
					logthis('Product: '.$product->get_title().' Stock management disabled');
				}
				return true;
			}else{
				// Update product data
				$sales_price = $product->get_price_excluding_tax();
				$client->Product_UpdateFromData(array(
				'data' => (object)array(
				'Handle' => $product_data->Handle,
				'Number' => $product_data->Number,
				'ProductGroupHandle' => $product_data->ProductGroupHandle,
				'Name' => $product->get_title(),
				'Description' => $this->woo_economic_product_content_trim($product->post->post_content, 255),
				'BarCode' => "",
				//'SalesPrice' => (isset($product->price) && !empty($product->price) ? $product->price : 0.0),
				'SalesPrice' => (isset($sales_price) && !empty($sales_price) ? $sales_price : 0.0),
				'CostPrice' => (isset($product_data->CostPrice) ? $product_data->CostPrice : 0.0),
				'RecommendedPrice' => $product_data->RecommendedPrice,
				/*'UnitHandle' => (object)array(
				'Number' => 1
				),*/
				'IsAccessible' => true,
				'Volume' => $product_data->Volume,
				//'DepartmentHandle' => isset($product_data->DepartmentHandle) ? $product_data->DepartmentHandle : '',
				//'DistributionKeyHandle' => isset($product_data->DistrubutionKeyHandle) ? $product_data->DistrubutionKeyHandle : '',
				'InStock' => $product_data->InStock,
				'OnOrder' => $product_data->OnOrder,
				'Ordered' => $product_data->Ordered,
				'Available' => $product_data->Available)))->Product_UpdateFromDataResult;
				
				//Update InStock from e-conomic to woocommerce
				if($product->managing_stock()){
					($product_data->InStock !=0 || $product_data->InStock =='') ? $product->set_stock($product_data->InStock) : logthis('Product stock not updated.');
					logthis('Product: '.$product->get_title().' Stock updated to '.$product_data->InStock);
				}else{
					logthis('Product: '.$product->get_title().' Stock management disabled');
				}
			}			
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
					if($this->product_sync != "on"){
						if($product->managing_stock()){
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product sync: Disabled! Use "Activate product sync" settings to enable it. <br> Product stock sync: Successfull!', 'woocommerce-e-conomic-integration') ));
						}else{
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product sync: Disabled! Use "Activate product sync" settings to enable it. <br> Product stock sync: Stock management disabled, Stock management can be enabled at Product->Inventory.', 'woocommerce-e-conomic-integration') ));
						}
					}else{
						if($product->managing_stock()){
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product sync: Successful! <br> Product stock sync: Successfull!', 'woocommerce-e-conomic-integration') ));
						}else{
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product sync: Successful! <br> Product stock sync: Stock management disabled, Stock management can be enabled at Product->Inventory.', 'woocommerce-e-conomic-integration') ));
						}
					}
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product not synced, someting went wrong. Please try product sync after some time!', 'woocommerce-e-conomic-integration') ));
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
     * Sync WooCommerce Products from e-conomic to WooCommerce.
     *
     * @access public
     * @param 
     * @return array log
     */
	 
	 public function sync_products_ew(){
		update_option('woo_save_object_to_economic', false);
		global $wpdb;
		$client = $this->woo_economic_client();
		$sync_log = array();
		$sync_log[0] = true;
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'msg' => __('Could not create e-conomic client, please try again later!', 'woocommerce-e-conomic-integration') ));
			return $sync_log;
		}
		
		$products = $client->Product_GetAll()->Product_GetAllResult;
		//logthis($products);
		
		$product_handles = array();
		
		foreach($products->ProductHandle as $product){
			$product_handles[$product->Number] = $client->Product_GetProductGroup(array('productHandle' => $product))->Product_GetProductGroupResult;
		}

		foreach($product_handles as $product_number => $group){
			$sku = str_replace($this->product_offset, '', $product_number);
			//logthis($group->Number);
			if($group->Number == $this->product_group){
				/*$productNumber = $client->Product_GetNumber(array(
					'productHandle' => array('Number' => $product_number ),
				))->Product_GetNumberResult;*/
				
				$product_name = $client->Product_GetName(array(
					'productHandle' => array('Number' => $product_number ),
				))->Product_GetNameResult;
				
				//logthis($product_number);
				//logthis($product_name);
				//logthis($sku);
				
				$product_post_ids = $wpdb->get_results("SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_sku' AND meta_value = '".$sku."'", OBJECT_K );
				//logthis($product_post_ids);
			
				if(!empty($product_post_ids)){
					foreach($product_post_ids as $product_post_id){
						$product_id = $product_post_id->post_id;
					}
					if(get_post_status( $product_id ) == 'trash'){
						continue;
					}
					//logthis('product_id: '.$product_id);
					//logthis('product_number: '.$product_number);
				}else{
					$product_id = NULL;
				}
				
				$product_data = $client->Product_GetData(array(
					'entityHandle' => array('Number' => $product_number ),
				))->Product_GetDataResult;	
				
				//logthis($product_data);
				
				if($product_id != NULL){
					logthis('update product : '.$product_number);
					
					if($this->product_sync == "on"){
						$product = new WC_Product($product_id);
						$post = array(
							'ID'		   => $product_id,
							'post_content' => $product_data->Description != ''? $product_data->Description : $product_data->Name,
							'post_title'   => $product_data->Name,
						);
						
						$post_id = wp_update_post( $post, true );
						if (is_wp_error($post_id)) {
							$errors = $post_id->get_error_messages();
							foreach ($errors as $error) {
								logthis($error);
							}
						}
						update_post_meta( $post_id, '_price', (int) $product_data->SalesPrice );
						//update_post_meta( $post_id, '_sale_price', (int) $product_data->SalesPrice );
						if($product->managing_stock()){
							if((int)$product_data->InStock > 0){
								$product->set_stock($product_data->InStock);
								update_post_meta( $post_id, '_stock_status', 'instock' );
							}else{
								$product->set_stock(0);
								update_post_meta( $post_id, '_stock_status', 'outofstock' );
							}
							logthis('Product: '.$product->get_title().' Stock updated to '.$product_data->InStock);
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Successful! <br> Product stock sync: Successfull!', 'woocommerce-e-conomic-integration') ));
						}else{
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Successful! <br> Product stock sync: Stock management disabled, Stock management can be enabled at Product->Inventory.', 'woocommerce-e-conomic-integration') ));
						}
					}else{
						if($product->managing_stock()){
							($product_data->InStock !=0 || $product_data->InStock =='') ? $product->set_stock($product_data->InStock) : logthis('Product stock not updated.');
							logthis('Product: '.$product->get_title().' Stock updated to '.$product_data->InStock);
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Disabled! Use "Activate product sync" settings to enable it. <br> Product stock sync: Successfull!', 'woocommerce-e-conomic-integration') ));
						}else{
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Disabled! Use "Activate product sync" settings to enable it. <br> Product stock sync: Stock management disabled, Stock management can be enabled at Product->Inventory.', 'woocommerce-e-conomic-integration') ));
						}
					}
				}else{
					logthis('add product : '.$product_number);
					$post = array(
						'post_status'  => 'publish',
						'post_type'    => 'product',
						'post_title'   => $product_data->Name,
						'post_content' => $product_data->Description != ''? $product_data->Description : $product_data->Name,
						'post_excerpt' => $product_data->Description != ''? $product_data->Description : $product_data->Name,
					);
					
					$post_id = wp_insert_post( $post, true );
					if (is_wp_error($post_id)) {
						$errors = $post_id->get_error_messages();
						foreach ($errors as $error) {
							logthis('Product creation error');
							logthis($error);
						}
						continue;
					}
					$product = new WC_Product($post_id);
					update_post_meta( $post_id, '_sku', $sku );
					update_post_meta( $post_id, '_price', (string) $product_data->SalesPrice );
					//update_post_meta( $post_id, '_sale_price', (int) $product_data->SalesPrice );
					if((int)$product_data->InStock > 0){
						$product->set_stock($product_data->InStock);
						update_post_meta( $post_id, '_stock_status', 'instock' );
					}else{
						$product->set_stock(0);
						update_post_meta( $post_id, '_stock_status', 'outofstock' );
					}

					array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Successful! <br> Product stock sync: Successfull!', 'woocommerce-e-conomic-integration') ));
				}
			}else{
				//$sync_log[0] = false;
				//array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'sku' => $product_number, 'msg' => __('Product group doesn\'t match the Product group in settings!', 'woocommerce-e-conomic-integration') ));
				logthis("Product group doesn't match the Product group in settings: ".$product_number);
			}
		}
		update_option('woo_save_object_to_economic', true);
		return $sync_log;
	}
	 
	 /**
     * Save WooCommerce Product to e-conomic
     *
     * @access public
     * @param product oject
     * @return bool
     */
	 
	 public function save_customer_to_economic(SoapClient &$client, WP_User $user = NULL, WC_Order $order = NULL){
	  logthis("save_customer_to_economic creating client");
	  global $wpdb;	
	  try {
		$debtorHandle = $this->woo_get_debtor_handle_from_economic($client, $user, $order);
		
		if (isset($debtorHandle)) {
			logthis("save_customer_to_economic woo_get_debtor_handle_from_economic handle returned: " . $debtorHandle->Number);
			
			$debtor_delivery_location_handle = $this->woo_get_debtor_delivery_location_handles_from_economic($client, $debtorHandle);
			
			foreach ($this->user_fields as $meta_key) {
				$this->woo_save_customer_meta_data_to_economic($client, $meta_key, $order ? $order->$meta_key: $user->get($meta_key), $debtorHandle, $debtor_delivery_location_handle, $user, $order);
			}
			
			if(is_object($order)){
				$email = $order->billing_email;
			}
			
			if(is_object($user)){
				$email = $user->get('billing_email');
			}
			
			logthis("save_customer_to_economic customer synced for email: " . $email);
			
			if($wpdb->query ("SELECT * FROM wce_customers WHERE email='".$email."';")){
				$wpdb->update ("wce_customers", array('synced' => 1, 'customer_number' => $debtorHandle->Number, 'email' => $email), array('email' => $email), array('%d', '%d', '%s'), array('%s'));
			}else{
				$wpdb->insert ("wce_customers", array('user_id' => $user->ID, 'customer_number' => $debtorHandle->Number, 'email' => $email, 'synced' => 1), array('%d', '%s', '%s', '%d'));
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
		if($wpdb->query ("SELECT * FROM wce_customers WHERE email=".$email." AND synced=0;")){
			return false;
		}else{
			$wpdb->insert ("wce_customers", array('user_id' => $user->ID, 'customer_number' => '0', 'email' => $email, 'synced' => 0), array('%d', '%s', '%s', '%d'));
			return false;
		}
	  }
	}
	
	/**
     * Save customer meta data to economic
     *
     * @access public
     * @param user object, $meta_key, $meta_value
     * @return void
     */
	public function woo_save_customer_meta_data_to_economic(SoapClient &$client, $meta_key, $meta_value, $debtor_handle, $debtor_delivery_location_handle, WP_User $user = NULL, WC_Order $order = NULL){
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
		} elseif ($meta_key == 'billing_address_1') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $adr1 = $order ? $order->billing_address_1 : $user->get('billing_address_1');
			  $adr2 = $order ? $order->billing_address_2 : $user->get('billing_address_2');
			  $state = $order ? $order->billing_state : $user->get('billing_state');
			  $billing_country = $order ? $order->billing_country : $user->get('billing_country');
			  $countries = new WC_Countries();		
			  $formatted_state = (isset($state)) ? $countries->states[$billing_country][$state] : "";
			  $formatted_adr = trim($adr1."\n".$adr2."\n".$formatted_state);
			  logthis("woo_save_customer_meta_data_to_economic adr1: " . $adr1 . ", adr2: " . $adr2 . ", state: " . $formatted_state);
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
	
		} elseif ($meta_key == 'billing_company') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $meta_value = $order ? $order->billing_company ? $order->billing_company : $order->billing_first_name.' '.$order->billing_last_name : $user->get('firstname');
			  $client->Debtor_SetName(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif($meta_key == 'billing_first_name' || $meta_key == 'billing_last_name') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $first = ($meta_key == 'billing_first_name') ? $meta_value : $order ? $order->billing_first_name : $user->get('billing_first_name');
			  $last = ($meta_key == 'billing_last_name') ? $meta_value : $order ? $order->billing_last_name : $user->get('billing_last_name');
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
		elseif($meta_key == 'shipping_address_1') {
			  logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $adr1 = $order ? $order->shipping_address_1 : $user->get('shipping_address_1');
			  $adr2 = $order ? $order->shipping_address_2 : $user->get('shipping_address_2');
			  $state = $order ? $order->shipping_state : $user->get('shipping_state');
			  $shipping_country = $order ? $order->shipping_country : $user->get('shipping_country');
			  $countries = new WC_Countries();
			  $formatted_state = (isset($state)) ? $countries->states[$shipping_country][$state] : "";
			  $formatted_adr = trim("$adr1\n$adr2\n$formatted_state");
			  logthis("woo_save_customer_meta_data_to_economic adr1: " . $adr1 . ", adr2: " . $adr2 . ", state: " . $formatted_state);
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
		$sync_log = array();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'msg' => __('Could not create e-conomic client, please try again later!', 'woocommerce-e-conomic-integration') ));
			return $sync_log;
		}
		$users = array();
		$orders = array();
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
        $unsynced_users = $wpdb->get_results("SELECT * FROM wce_customers WHERE synced = 0 AND user_id != 0");
		foreach ($unsynced_users as $user){
			array_push($users, new WP_User($user->user_id));
		}
		
		$unsynced_guest_users = $wpdb->get_results("SELECT * FROM wce_customers WHERE synced = 0 AND user_id = 0");
		foreach ($unsynced_guest_users as $guest_user){
			$unsynced_guest_user_orders = $wpdb->get_results("SELECT * FROM  ".$wpdb->prefix."postmeta WHERE  meta_value = '".$guest_user->email."' ORDER BY post_id DESC");
			foreach ($unsynced_guest_user_orders as $order){
				array_push($orders, new WC_Order($order->post_id));
				break;
			}
		}
		
		//logthis($users);
		if(!empty($users)){
			foreach ($users as $user) {
				logthis('sync_contacts User ID: ' . $user->ID);
				if($this->save_customer_to_economic($client, $user)){
					array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'user_id' => $user->ID, 'msg' => __('Customer synced successfully', 'woocommerce-e-conomic-integration') ));
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'user_id' => $user->ID, 'msg' => __('Sync failed, please try again later!', 'woocommerce-e-conomic-integration') ));
				}
			}
		}elseif(!empty($orders)){
			foreach ($orders as $order) {
				logthis('sync_contacts User email (guest user): ' . $order->billing_email);
				if($this->save_customer_to_economic($client, NULL, $order)){
					array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'user_id' => $order->billing_email, 'msg' => __('Guest customer synced successfully', 'woocommerce-e-conomic-integration') ));
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'user_id' => $order->billing_email, 'msg' => __('Guest customer sync failed, please try again later!', 'woocommerce-e-conomic-integration') ));
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
     * Sync e-conomice users to  WooCommerce
     *
     * @access public
     * @param 
     * @return array log
     */
	 
	public function sync_contacts_ew(){
		global $wpdb;
		$client = $this->woo_economic_client();
		$sync_log = array();
		$sync_log[0] = true;
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'msg' => __('Could not create e-conomic client, please try again later!', 'woocommerce-e-conomic-integration') ));
			return $sync_log;
		}
		
		$debtors = $client->Debtor_GetAll()->Debtor_GetAllResult;
		//logthis($debtors);
		
		$debtor_handles = array();
		
		foreach($debtors->DebtorHandle as $debtor){
			$debtor_handles[$debtor->Number] = $client->Debtor_GetDebtorGroup(array('debtorHandle' => $debtor))->Debtor_GetDebtorGroupResult;
		}
		//logthis('debtor_handle_list');
		foreach($debtor_handles as $debtor_number => $group){
			if($group->Number == $this->customer_group){
				$debtor_email = $client->Debtor_GetEmail(array(
					'debtorHandle' => array('Number' => $debtor_number ),
				))->Debtor_GetEmailResult;
				
				$debtor_name = explode(' ', $client->Debtor_GetName(array(
					'debtorHandle' => array('Number' => $debtor_number ),
				))->Debtor_GetNameResult);
				
				//logthis($debtor_email);
				//logthis($debtor_name);
				
				$user = get_user_by( 'email', $debtor_email );
								
				if($wpdb->query('SELECT user_id FROM '.$wpdb->prefix.'usermeta WHERE meta_key = "debtor_number" AND meta_value = '.$debtor_number)){
					logthis('update customer meta: '.$debtor_number);
					$userdata = array(
						'ID' => $user->ID,
						//'user_login' => strtolower($debtor_name[0]),
						'first_name' => $debtor_name[0],
						'last_name' => $debtor_name[1],
						'user_email' => $debtor_email,
						//'role' => 'customer'
					);
					
					$customer = wp_update_user( $userdata );
					//logthis($customer);
					if ( ! is_wp_error( $customer ) ) {
						logthis("User updated : ". $customer ." for debtor_number: ". $debtor_number);
						$sync_log[0] = true;
						update_user_meta($customer, 'debtor_number', $debtor_number);
						array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'user_id' => $customer, 'msg' => __('User '.$debtor_name[0].' '.$debtor_name[1].' with customer role updated!', 'woocommerce-e-conomic-integration') ));	
					}else{
						logthis($customer);
					}
				}else{					
					if($user){
						logthis('update customer: '.$debtor_number);
						$userdata = array(
							'ID' => $user->ID,
							//'user_login' => strtolower($debtor_name[0]),
							'first_name' => $debtor_name[0],
							'last_name' => $debtor_name[1],
							'user_email' => $debtor_email,
							//'role' => 'customer'
						);
						
						$customer = wp_update_user( $userdata );
						//logthis($customer);
						if ( ! is_wp_error( $customer ) ) {
							logthis("User updated : ". $customer ." for debtor_number: ". $debtor_number);
							$sync_log[0] = true;
							update_user_meta($customer, 'debtor_number', $debtor_number);
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'user_id' => $customer, 'msg' => __('User '.$debtor_name[0].' '.$debtor_name[1].' with customer role updated!', 'woocommerce-e-conomic-integration') ));	
						}else{
							logthis($customer);
						}
					}else{
						logthis('add new customer: '.$debtor_number);
						$userdata = array(
							'user_login' => strtolower($debtor_name[0].$debtor_number),
							'first_name' => $debtor_name[0],
							'last_name' => $debtor_name[1],
							'user_email' => $debtor_email,
							'role' => 'customer'
						);
						
						$customer = wp_insert_user( $userdata );
						//logthis($customer);
						if ( ! is_wp_error( $customer ) ) {
							logthis("User created : ". $customer ." for debtor_number: ". $debtor_number);
							$sync_log[0] = true;
							update_user_meta($customer, 'debtor_number', $debtor_number);
							array_push($sync_log, array('status' => __('success', 'woocommerce-e-conomic-integration'), 'user_id' => $customer, 'msg' => __('New user '.$debtor_name[0].' '.$debtor_name[1].' with customer role created!', 'woocommerce-e-conomic-integration') ));	
							if(!$wpdb->query ("SELECT * FROM wce_customers WHERE email='".$debtor_email."'")){
								$wpdb->insert ("wce_customers", array('user_id' => $customer, 'customer_number' => $debtor_number, 'email' => $debtor_email, 'synced' => 1), array('%d', '%s', '%s', '%d'));
							}
						}else{
							if(!$wpdb->query ("SELECT * FROM wce_customers WHERE email='".$debtor_email."'")){
								$wpdb->insert ("wce_customers", array('user_id' => 0, 'customer_number' => $debtor_number, 'email' => $debtor_email, 'synced' => 0), array('%d', '%s', '%s', '%d'));
							}
						}
					}								
				}
			}else{
				//$sync_log[0] = false;
				//array_push($sync_log, array('status' => __('fail', 'woocommerce-e-conomic-integration'), 'user_id' => NULL, 'msg' => __('Customer group doesn\'t match the Customer group in settings!', 'woocommerce-e-conomic-integration') ));
				logthis("Customer group doesn't match the Customer group in settings: ".$customer);
			}
		}
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
			/*'UnitHandle' => (object)array(
			'Number' => 1
			),*/
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
	public function send_invoice_economic(SoapClient &$client, WC_Order $order = NULL){
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
			logthis('send_invoice_economic calling mail_attachment');
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
		logthis('mail_attachment sending mail');
		return mail($mailto, $subject, "", $header);
	}

}