<?php
//php.ini overriding necessary for communicating with the SOAP server.
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(1);
ini_set("default_socket_timeout", 6000);
class WCE_API{

    /** @public String base URL */
    public $api_url;
	
    
    /** @public String Agreement Number */
    public $agreementNumber;

    /** @public String User Name */
    public $username;

    /** @public String Password */
    public $password;
	
	/** @public Number corresponding the customer group */
    public $customer_group;
	
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
		
        $this->api_url = dirname(__FILE__)."/EconomicWebservice.asmx.xml";
        $this->agreementNumber = $options['agreementNumber'];
        $this->username = $options['username'];
        $this->password = $options['password'];
		$this->customer_group = $options['customer-group'];
    }
	
	/**
     * Creates a e-conomic HttpRequest
     *
     * @access public
     * @return bool
     */
    public function create_API_validation_request(){
		//wce_logthis(get_option('woocommerce_economic_general_settings'));
        wce_logthis("API VALIDATION");
		
		if($this->woo_economic_client()){
			return true;
		}
		else{
			wce_logthis("API VALIDATION FAILED: client not connected!");
			return false;
		}
    }

    /**
     * Create Connection to e-conomic
     *
     * @access public
     * @return object
     */
    public function woo_economic_client(){
	
	  $client = new SoapClient($this->api_url, array("trace" => 1, "exceptions" => 1));
	
	  wce_logthis("woo_economic_client loaded agreementNumber: " . $this->agreementNumber . " usernamme: " . $this->username . " and password: ". $this->password);
	  if (!$this->agreementNumber || !$this->username || !$this->password)
		die("e-conomic agreementNumber, username, and password are not defined");
	  wce_logthis("woo_economic_client - options are OK!");
	  
	  wce_logthis("woo_economic_client - creating client...");
	  	  
	  try{
		 $client->Connect(array(
		'agreementNumber' => $this->agreementNumber,
		'userName' => $this->username,
		'password' => $this->password));
	  }
	  catch (Exception $exception){
		wce_logthis("Connection to client failed: " . $exception->getMessage());
		$this->debug_client($client);
		return false;
	  }
	  
	  wce_logthis("woo_economic_client - client created");
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
		wce_logthis("Client is null");
	  } else {
		wce_logthis("------------");
		wce_logthis($client->__getLastRequestHeaders());
		wce_logthis("------------");
		wce_logthis($client->__getLastRequest());
		wce_logthis("------------");
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
		wce_logthis("woo_get_debtor_handle_from_economic trying to load : " . $debtorNumber);
		if (!isset($debtorNumber) || empty($debtorNumber)) {
			wce_logthis("woo_get_debtor_handle_from_economic no handle found");
			return null;
		}
		
		$debtor_handle = $client->Debtor_FindByNumber(array(
		'number' => $debtorNumber
		))->Debtor_FindByNumberResult;
		
		if (isset($debtor_handle))
			wce_logthis("woo_get_debtor_handle_from_economic debtor found for user->id " . $user->ID);
		else {
			wce_logthis("woo_get_debtor_handle_from_economic debtor not found");
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
			wce_logthis("woo_get_debtor_delivery_location_handles_from_economic no handle found");
			return null;
		}
		
		wce_logthis("woo_get_debtor_delivery_location_handles_from_economic getting delivery locations available for debtor debtor_delivery_location_handles");
		//wce_logthis($debtor_handle);
		$debtor_delivery_location_handles = $client->Debtor_GetDeliveryLocations(array(
		'debtorHandle' => $debtor_handle
		))->Debtor_GetDeliveryLocationsResult;
		
		//wce_logthis("debtor_delivery_location_handles");
		//wce_logthis($debtor_delivery_location_handles);
		
		if (isset($debtor_delivery_location_handles->DeliveryLocationHandle->Id)){
			wce_logthis("woo_get_debtor_delivery_location_handles_from_economic delivery location handle ID: ");
			wce_logthis($debtor_delivery_location_handles->DeliveryLocationHandle->Id);
			return $debtor_delivery_location_handles->DeliveryLocationHandle;
		}
		else {
			$debtor_delivery_location_handle = $client->DeliveryLocation_Create(array(
			'debtorHandle' => $debtor_handle
			))->DeliveryLocation_CreateResult;
			wce_logthis("woo_get_debtor_delivery_location_handles_from_economic delivery location handle: ");
			wce_logthis($debtor_delivery_location_handle);
			return $debtor_delivery_location_handle;
		}
	}
    
	 
	 /**
     * Save WooCommerce Customer to e-conomic
     *
     * @access public
     * @param product oject
     * @return bool
     */
	 
	 public function save_customer_to_economic(WP_User $user, SoapClient &$client){
	  wce_logthis("save_customer_to_economic creating client");
	  global $wpdb;	
	  try {
		$debtorHandle = $this->woo_get_debtor_handle_from_economic($user, $client);
		if (!isset($debtorHandle)) { // The debtor doesn't exist - lets create it
		  $debtor_grouphandle_meta = $this->customer_group;
		  wce_logthis("save_customer_to_economic debtor group: " . $debtor_grouphandle_meta);
		  //wce_logthis($user);
		  wce_logthis("save_customer_to_economic name: " . $user->get('first_name') != '' ? $user->get('first_name') : $user->get('billing_first_name') . " " . $user->get('last_name') != '' ? $user->get('last_name') : $user->get('billing_last_name'));
		  wce_logthis("save_customer_to_economic billing_comnpany: " . $user->get('billing_company'));

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
			wce_logthis("save_customer_to_economic woo_get_debtor_handle_from_economic handle returned: " . $debtorHandle->Number);
			
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
			wce_logthis("save_customer_to_economic debtor not found.");
			return false;
		}
	  } catch (Exception $exception) {
		wce_logthis("save_customer_to_economic could not save user to e-conomic: " . $exception->getMessage());
		$this->debug_client($client);
		wce_logthis("Could not create user.");
		wce_logthis($exception->getMessage());
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
	  $customer_offset = $this->customer_offset;
	  $customer_id = $customer_offset.$user->ID;
	  wce_logthis("woo_get_customer_id id: " . $customer_id);
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
	  wce_logthis("woo_save_customer_meta_data_to_economic updating client");
	  //wce_logthis($debtor_handle);
	  //wce_logthis($debtor_delivery_location_handle);
	  if (!isset($debtor_handle)) {
		wce_logthis("woo_save_customer_meta_data_to_economic debtor not found, can not update meta");
		return;
	  }
	  try {
	
		if ($meta_key == 'billing_phone') {
		  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
		  $client->Debtor_SetTelephoneAndFaxNumber(array(
			'debtorHandle' => $debtor_handle,
			'value' => $meta_value
		  ));
		} elseif ($meta_key == 'billing_email') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetEmail(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_country') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $countries = new WC_Countries();
			  $country = $countries->countries[$meta_value];
			  wce_logthis("woo_save_customer_meta_data_to_economic country: " . $country);
			  $client->Debtor_SetCountry(array(
				'debtorHandle' => $debtor_handle,
				'value' => $country
		  ));
		} elseif ($meta_key == 'billing_address_1' || $meta_key == 'billing_address_2' || $meta_key == 'billing_state') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $adr1 = ($meta_key == 'billing_address_1') ? $meta_value : $user->get('billing_address_1');
			  $adr2 = ($meta_key == 'billing_address_2') ? $meta_value : $user->get('billing_address_2');
			  $state = ($meta_key == 'billing_state') ? $meta_value : $user->get('billing_state');
			  $billing_country = $user->get('billing_country');
			  $countries = new WC_Countries();
		
			  $formatted_state = (isset($state)) ? $countries->states[$billing_country][$state] : "";
			  $formatted_adr = trim("$adr1\n$adr2\n$formatted_state");
			  wce_logthis("woo_save_customer_meta_data_to_economic adr1: " . $adr1 . " adr2: " . $adr2 . " state " . $formatted_state);
			  wce_logthis("woo_save_customer_meta_data_to_economic formatted_adr: " . $formatted_adr);
			  $client->Debtor_SetAddress(array(
				'debtorHandle' => $debtor_handle,
				'value' => $formatted_adr
			  ));
	
		} elseif ($meta_key == 'billing_postcode') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetPostalCode(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_city') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetCity(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_company' && $meta_value != '') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetName(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif($meta_key == 'billing_first_name' || $meta_key == 'billing_last_name') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
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
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $countries = new WC_Countries();
			  $country = $countries->countries[$meta_value];
			  wce_logthis("woo_save_customer_meta_data_to_economic country: " . $country);
			  $client->DeliveryLocation_SetCountry (array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $country
			  ));
		} elseif ($meta_key == 'shipping_postcode') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->DeliveryLocation_SetPostalCode(array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'shipping_city') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $client->DeliveryLocation_SetCity (array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $meta_value
			  ));
	
		}
		elseif($meta_key == 'shipping_address_1' || $meta_key == 'shipping_address_2' || $meta_key == 'shipping_state') {
			  wce_logthis("woo_save_customer_meta_data_to_economic key: " . $meta_key . " value: " . $meta_value);
			  $adr1 = ($meta_key == 'shipping_address_1') ? $meta_value : $user->get('shipping_address_1');
			  $adr2 = ($meta_key == 'shipping_address_2') ? $meta_value : $user->get('shipping_address_2');
			  $state = ($meta_key == 'shipping_state') ? $meta_value : $user->get('shipping_state');
			  $shipping_country = $user->get('shipping_country');
			  $countries = new WC_Countries();
			  $formatted_state = (isset($state)) ? $countries->states[$shipping_country][$state] : "";
			  $formatted_adr = trim("$adr1\n$adr2\n$formatted_state");
			  wce_logthis("woo_save_customer_meta_data_to_economic adr1: " . $adr1 . " adr2 " . $adr2 . " state " . $formatted_state);
			  //wce_logthis("debtor_delivery_location_handle:");
			  //wce_logthis($debtor_delivery_location_handle);
			  $client->DeliveryLocation_SetAddress(array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $formatted_adr
			  ));
		} else{
			wce_logthis("woo_save_customer_meta_data_to_economic unknown meta_key :".$meta_key." meta_value: ".$meta_value);
		}
		return true;
	  } catch (Exception $exception) {
		wce_logthis("woo_save_customer_meta_data_to_economic could not update debtor: " . $exception->getMessage());
		$this->debug_client($client);
		wce_logthis("Could not update debtor.");
		wce_logthis($exception->getMessage());
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
			array_push($sync_log, array('status' => 'fail', 'msg' => 'Could not create e-conomic client, please try again later!' ));
			return $sync_log;
		}
		$users = array();
		$sync_log = array();
		$sync_log[0] = true;
		wce_logthis("sync_contacts starting...");
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
		
		//wce_logthis($users);
		if(!empty($users)){
			foreach ($users as $user) {
				wce_logthis('sync_contacts User ID: ' . $user->ID);
				if($this->save_customer_to_economic($user, $client)){
					array_push($sync_log, array('status' => 'success', 'user_id' => $user->ID, 'msg' => 'Customer synced successfully' ));
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => 'fail', 'user_id' => $user->ID, 'msg' => 'Sync failed, please try again later!' ));
				}
			}
		}else{
			$sync_log[0] = true;
			array_push($sync_log, array('status' => 'success', 'user_id' => '', 'msg' => 'All customers were already synced!' ));
		}

		$client->Disconnect();
		wce_logthis("sync_contacts ending...");
		return $sync_log;
	}

}