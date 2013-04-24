<?php if (!defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
do_action('action_hook_espresso_log', __FILE__, ' FILE LOADED', '' );
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license				http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link					http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * Payment Model
 *
 * @package			Event Espresso
 * @subpackage	includes/models/
 * @author				Sidney Harrell
 *
 * ------------------------------------------------------------------------
 */
Class EEM_Gateways {

	private static $_instance = NULL;
	private $_active_gateways = array();
	private $_all_gateways = array();
	private $_gateway_instances = array();
	private $_payment_settings = array();
	private $_selected_gateway = NULL;
	private $_hide_other_gateways = FALSE;
	private $_session_gateway_data = NULL;
	private $_off_site_form = NULL;
	private $_ajax = TRUE;



	/**
	 * 		@singleton method used to instantiate class object
	 * 		@access public
	 * 		@return class instance
	 */
	public static function instance() {
		// check if class object is instantiated
		if (self::$_instance === NULL or !is_object(self::$_instance) or !is_a(self::$_instance, __CLASS__)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}



	/**
	 * 		class constructor
	 * 		@access private
	 * 		@return void
	 */
	private function __construct() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		//so client code can check for instatiation b4 including
		define('ESPRESSO_GATEWAYS', TRUE);
		require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Gateway.class.php');
		require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Offline_Gateway.class.php');
		require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Offsite_Gateway.class.php');
		require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Onsite_Gateway.class.php');

		$this->_load_session_gateway_data();
		$this->_set_active_gateways();
		$this->_load_payment_settings();
		$this->_scan_and_load_all_gateways();
	}



	/**
	 * 		load_session_gateway_data
	 * 		try to pull data from session b4 hitting db
	 * 		@access private
	 * 		@return void
	 */
	private function _load_session_gateway_data() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		global $EE_Session;
		$this->_session_gateway_data = $EE_Session->get_session_data(FALSE, 'gateway_data');
		if (!empty($this->_session_gateway_data['selected_gateway'])) {
			$this->_selected_gateway = $this->_session_gateway_data['selected_gateway'];
		}
		if (!empty($this->_session_gateway_data['hide_other_gateways'])) {
			$this->_hide_other_gateways = $this->_session_gateway_data['hide_other_gateways'];
		}
		if (!empty($this->_session_gateway_data['off_site_form'])) {
			$this->_off_site_form = $this->_session_gateway_data['off_site_form'];
		}
		if (!empty($this->_session_gateway_data['ajax'])) {
			$this->_ajax = $this->_session_gateway_data['ajax'];
		}
	}



	/**
	 * 		set_active_gateways
	 * 		@access public
	 * 		@return void
	 */
	public function set_active_gateways() {
		$this->_set_active_gateways();
	}



	/**
	 * 		_set_active_gateways
	 * 		@access private
	 * 		@return void
	 */
	private function _set_active_gateways() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($this->_session_gateway_data['active_gateways'])) {
			$this->_active_gateways = $this->_session_gateway_data['active_gateways'];
			
		} else {
			global $espresso_wp_user, $EE_Session;
			$this->_active_gateways = get_user_meta($espresso_wp_user, 'active_gateways', TRUE);
			if (!is_array($this->_active_gateways)) {
				$this->_active_gateways = array();
			}
			$EE_Session->set_session_data(array('active_gateways' => $this->_active_gateways), 'gateway_data');
		}
	}



	/**
	 * 		_load_payment_settings
	 * 		@access private
	 * 		@return void
	 */
	private function _load_payment_settings() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($this->_session_gateway_data['payment_settings'])) {
			$this->_payment_settings = $this->_session_gateway_data['payment_settings'];
		} else {
			global $espresso_wp_user, $EE_Session;
			$this->_payment_settings = get_user_meta($espresso_wp_user, 'payment_settings', TRUE);
			if (!is_array($this->_payment_settings)) {
				$this->_payment_settings = array();
			}
			$EE_Session->set_session_data(array('payment_settings' => $this->_payment_settings), 'gateway_data');
		}

		//echo printr( $this->_payment_settings, __CLASS__ . ' ->' . __FUNCTION__ . ' ( line #' .  __LINE__ . ' )' );
	}




	/**
	 * 		_scan_and_load_all_gateways
	 * 		@access private
	 * 		@return void
	 */
	private function _scan_and_load_all_gateways() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		// on the settings page, scan and load all the gateways
		if (is_admin() && !empty($_GET['page']) && $_GET['page'] == 'espresso_payment_settings') {
			$this->_load_all_gateway_files();
		} else {
			// if something went wrong, fail gracefully
			if ( ! is_array($this->_active_gateways)) {	
				$msg = __( 'There are no active payment gateways. Please configure at least one gateway in the Event Espresso Payment settings page.', 'event_espresso'); 
				EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
				return;
			}
			foreach ($this->_active_gateways as $gateway => $in_uploads) {
				$classname = 'EE_' . $gateway;
				$filename = $classname . '.class.php';
				if ($in_uploads) {
					if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . $filename)) {
						require_once(EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . $filename);
					} else {
						$this->unset_active($gateway);	// if it can't find a gateway, delete it from the active_gateways
					}
				} else {
					if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway . DS . $filename)) {
						require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway . DS . $filename);
					} else {
						$this->unset_active($gateway);	// if it can't find a gateway, delete it from the active_gateways
					}
				}
				if (class_exists($classname)) {
					//$gateway = $classname::instance($this);
					$that = &$this;
					$gateway = call_user_func(array($classname, 'instance'), $that);
					$this->_gateway_instances[$gateway->gateway()] = $gateway;
				}
			}
		}
	}




	/**
	 * 		_load_all_gateway_files
	 * 		@access private
	 * 		@return void
	 */
	private function _load_all_gateway_files() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		
		$gateways = array();
		$upload_gateways = array();
		// grab standard gateways
		if ( ! $gateways_glob = glob(EVENT_ESPRESSO_PLUGINFULLPATH . "gateways" . DS . "*", GLOB_ONLYDIR)) {
			$gateways_glob = array();
		}
		// grab custom gateways
		if ( ! $upload_gateways_glob = glob(EVENT_ESPRESSO_GATEWAY_DIR . '*', GLOB_ONLYDIR)) {
			$upload_gateways_glob = array();
		}
		// grab gateway folder names only
		foreach ($gateways_glob as $gateway) {
			$sub = basename( $gateway );
			$gateways[$sub] = FALSE;
		}
		// grab gateway folder names only
		foreach ($upload_gateways_glob as $upload_gateway) {
			$sub = basename( $gateway );
			$upload_gateways[$sub] = TRUE;
		}
		
		$this->_all_gateways = array_merge($upload_gateways, $gateways);
		//printr( $this->_all_gateways, '_all_gateways  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

		foreach ($this->_all_gateways as $gateway => $in_uploads) {
			$classname = 'EE_' . $gateway;
			$filename = $classname . '.class.php';
			if ($in_uploads) {
				if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . $filename)) {
					require_once(EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . $filename);
				} else {
					$msg = sprintf(
							__( 'The file : %s could not be located in : %s', 'event_espresso'), 
							'<b>' . $filename . '</b>',
							'<b>' . EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . '</b>' 
					);
					EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
				}
			} else if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway . DS . $filename)) {
//				echo '<h4>$filename : ' . $filename . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
				require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway . DS . $filename);
			} else {
				$msg = sprintf(
						__( 'The file : %s could not be located in either : %s or %s', 'event_espresso'), 
						'<b>' . $filename . '</b>',
						'<b>' . EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . '</b>',
						'<b>' . EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway . DS . '</b>'
				);
				$msg .= '||' . __( 'For the time being, the following error only indicates that the Gateway has yet to be converted over to 4.0 : ', 'event_espresso') . '<br />' . $msg;
				EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
			}

			if (class_exists($classname)) {
				//echo '<h4>$classname : ' . $classname . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
				$that = &$this;
				$gateway = call_user_func(array($classname, 'instance'), $that);
				$this->_gateway_instances[$gateway->gateway()] = $gateway;
			}
		}
	}



	/**
	 * 		get payment settings for specific gateway
	 * 		@access public
	* 		@param	string	$gateway
	 * 		@return 	mixed	array on success FALSE on fail
	 */
	public function payment_settings($gateway = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($this->_payment_settings[$gateway])) {
			return $this->_payment_settings[$gateway];
		} else {
			return FALSE;
		}
	}



	/**
	 * 		update payment settings for specific gateway
	 * 		@access public
	* 		@param	string	$gateway
	* 		@param	array	$settings
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function update_payment_settings($gateway = FALSE, $settings = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$gateway || !$settings) {
			return FALSE;
		}
		$this->_payment_settings[$gateway] = $settings;

		global $espresso_wp_user;

		if (update_user_meta($espresso_wp_user, 'payment_settings', $this->_payment_settings)) {
			$msg = __('Payment Settings Updated!', 'event_espresso');
			EE_Error::add_success( $msg, __FILE__, __FUNCTION__, __LINE__ );
			return TRUE;
		} else {
			$msg = __('Payment Settings were not saved! ', 'event_espresso');
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
			return FALSE;
		}
	}



	/**
	 * 		is gateway active ?
	 * 		@access public
	* 		@param	string	$gateway
	 * 		@return 	mixed	array on success FALSE on fail
	 */
	public function is_active($gateway = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$gateway) {
			return FALSE;
		}
		if (array_key_exists($gateway, $this->_active_gateways)) {
			return array($gateway => $this->_active_gateways[$gateway]);
		} else {
			return FALSE;
		}
	}



	/**
	 * 		is gateway in the uploads dir ?
	 * 		@access public
	* 		@param	string	$gateway
	 * 		@return 	mixed	array on success FALSE on fail
	 */
	public function is_in_uploads($gateway = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$gateway) {
			return FALSE;
		}
		if (array_key_exists($gateway, $this->_all_gateways)) {
			return array($gateway => $this->_all_gateways[$gateway]);
		} else {
			return FALSE;
		}
	}



	/**
	 * 		set gateway as active and available during registration
	 * 		@access public
	* 		@param	string	$gateway
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function set_active($gateway = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$gateway) {
			return FALSE;
		}
		if (array_key_exists($gateway, $this->_all_gateways)) {
			$this->_active_gateways[$gateway] = $this->_all_gateways[$gateway];
			global $espresso_wp_user;
			if (update_user_meta($espresso_wp_user, 'active_gateways', $this->_active_gateways)) {
				$msg = $gateway . __(' Gateway Activated!', 'event_espresso');
				EE_Error::add_success( $msg, __FILE__, __FUNCTION__, __LINE__ );
				return TRUE;
			} else {
				$msg = $gateway . __(' Gateway Not Activated! ', 'event_espresso');
				EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}



	/**
	 * 		unset gateway as active and available during registration
	 * 		@access public
	* 		@param	string	$gateway
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function unset_active($gateway = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$gateway) {
			return FALSE;
		}
		if (array_key_exists($gateway, $this->_active_gateways)) {
			unset($this->_active_gateways[$gateway]);
			global $espresso_wp_user;
			if (update_user_meta($espresso_wp_user, 'active_gateways', $this->_active_gateways)) {
				$msg =$gateway .  __('Gateway Deactivated!', 'event_espresso');
				EE_Error::add_success( $msg, __FILE__, __FUNCTION__, __LINE__ );
				return TRUE;
			} else {
				$msg = $gateway . __('Gateway Not Deactivated! ', 'event_espresso');
				EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}



	/**
	 * 		set gateway as selected
	 * 		@access public
	* 		@param	string	$gateway
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function set_selected_gateway($gateway = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$gateway || !array_key_exists($gateway, $this->_active_gateways)) {
			return FALSE;
		} 
		
		$this->_selected_gateway = $gateway;
		$this->_hide_other_gateways = TRUE;
		$this->_set_session_data();
		foreach ($this->_active_gateways as $gateway => $in_uploads) {
			if ($this->_selected_gateway == $gateway) {
				$this->_gateway_instances[$gateway]->set_selected();
			} else {
				$this->_gateway_instances[$gateway]->set_hidden();
			}
		}
		return TRUE;

	}


	/**
	 * just return the gateway_instances for usage
	 * @return array EE_Gateway objects
	 */
	public function get_gateway_instances() {
		return $this->_gateway_instances;
	}
	


	/**
	 * 		unset gateway as selected
	 * 		@access public
	 * 		@return 	boolean	TRUE 
	 */
	public function unset_selected_gateway() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		$this->_selected_gateway = NULL;
		$this->_hide_other_gateways = FALSE;
		$this->_set_session_data();
		foreach ($this->_active_gateways as $gateway => $in_uploads) {
			$this->_gateway_instances[$gateway]->unset_selected();
		}
		return TRUE;
	}



	/**
	 * 		get selected gateway
	 * 		@access public
	 * 		@return 	string
	 */
	public function selected_gateway() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		return $this->_selected_gateway;
	}



	/**
	 * 		set_form_url
	 * 		@access public
	* 		@param	string	$base_url
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function set_form_url($base_url = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$base_url) {
			return FALSE;
		}
		foreach ($this->_active_gateways as $gateway => $in_uploads) {
			if (!empty($this->_gateway_instances[$gateway])) {
				$this->_gateway_instances[$gateway]->set_form_url($base_url);
			} else {
				return FALSE;
			}
		}
		return TRUE;
	}



	/**
	 * 		get gateway type
	 * 		@access public
	 * 		@return 	mixed	string on success FALSE on fail
	 */
	public function type() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($this->_payment_settings[$this->_selected_gateway]['type'])) {
			return $this->_payment_settings[$this->_selected_gateway]['type'];
		} else {
			return FALSE;
		}
	}



	/**
	 * 		get gateway display name
	 * 		@access public
	 * 		@return 	mixed	string on success FALSE on fail
	 */
	public function display_name() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($this->_payment_settings[$this->_selected_gateway]['display_name'])) {
			return $this->_payment_settings[$this->_selected_gateway]['display_name'];
		} else {
			return FALSE;
		}
	}



	/**
	 * 		get gateway off_site_form
	 * 		@access public
	 * 		@return 	mixed	string on success FALSE on fail
	 */
	public function off_site_form() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($this->_off_site_form)) {
			return $this->_off_site_form;
		} else {
			return FALSE;
		}
	}



	/**
	 * 		set_off_site_form
	 * 		@access public
	* 		@param	array	$form_data
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function set_off_site_form($form_data = FALSE) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$form_data) {
			return FALSE;
		}
		$this->_off_site_form = $form_data;
		$this->_set_session_data();
		return TRUE;
	}



	/**
	 * 		set_session_data
	 * 		@access public
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	private function _set_session_data() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		global $EE_Session;
		
		$session_data = array(
						'selected_gateway' => $this->_selected_gateway,
						'hide_other_gateways' => $this->_hide_other_gateways,
						'off_site_form' => $this->_off_site_form,
						'ajax' => $this->_ajax
				);
		// returns TRUE or FALSE
		return $EE_Session->set_session_data( $session_data, 'gateway_data' );		

	}



	/**
	 * 		set_ajax
	 * 		@access public
	* 		@param	boolean	$on_or_off
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function set_ajax( $on_or_off ) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if ( ! is_bool( $on_or_off )) {
			$this->_notices['errors'][] = __( 'An error occured. Set Ajax requires a boolean paramater.', 'event_espresso' );
			return FALSE;
		}
		$this->_ajax = $on_or_off;
		return $this->_set_session_data();
	}




	/**
	 * 		get ajax - whether request is regular HTML or via ajax
	 * 		@access public
	 * 		@return 	boolean
	 */
	public function ajax() {
		return $this->_ajax;
	}



	/**
	 * 		reset_session_data
	 * 		@access public
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function reset_session_data() {

		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		global $EE_Session;
	
		foreach ($this->_active_gateways as $gateway => $in_uploads) {
			if (!empty($this->_gateway_instances[$gateway])) {
				$this->_gateway_instances[$gateway]->reset_session_data();
			} else {
				return FALSE;
			}
		}
		
		$this->_selected_gateway = NULL;
		$this->_hide_other_gateways = FALSE;
		$this->_off_site_form = NULL;
		$this->_ajax = TRUE;
		$this->_payment_settings = array();
		$this->_active_gateways = array();
		
		$EE_Session->set_session_data(
				array(
							'selected_gateway' => $this->_selected_gateway,
							'hide_other_gateways' => $this->_hide_other_gateways,
							'off_site_form' => $this->_off_site_form,
							'ajax' => $this->_ajax,
							'payment_settings' => $this->_payment_settings,
							'active_gateways' => $this->_active_gateways
						), 
						'gateway_data'
				);
						
		//espresso_clear_session(); this seemed silly. we just expelled
		//all this effort clearing specific gateway items in the session, and now
		//we're clearing teh whole thing? no, that must have been an error.
		//so says Mike, March 27th 2013
		
		return TRUE;
	}
	
	/**
	 * Requires the given gateway for later use. Does not instantiate the gateway, however.
	 * By default, searches for the gateway named $gateway in 
	 * wp-uploads/espresso/gateways/$gateway/EE_$gateway.class.php
	 * next in
	 * wp-content/plugins/{event-espresso}/gateawys/$gateway/EE_$gateway}.class.php.
	 * Gateway developers may use filter_hook_espresso__EEM_Gateways__get_gateway_filepath__default_filepath to override the filepath,
	 * or filter_hook_espresso__EEM_Gateways__get_gateway_filepath__filename to override the file's name,
	 * or filter_hook_espresso__EEM_Gateways__get_gateway_filepath__classname to override the class's name, given the gateway's name.
	 * Each of these filters is passed the gateway's name.
	 * 
	 * @param string $gateway_name usually the folder's name where the gateway is stored. Eg, Paypal_Standard, 2checkout, Check, etc.
	 * @return void just requires the gateway. Doesn't return it
	 */
	/*public static function require_gateway($gateway_name){
		$classname = apply_filters('filter_hook_espresso__EEM_Gateways__get_gateway_filepath__classname', 'EE_' . $gateway_name,$gateway_name);
		$filename = apply_filters('filter_hook_espresso__EEM_Gateways__get_gateway_filepath__filename',$classname . '.class.php',$gateway_name);
		$default_gateway_filepath = apply_filters('filter_hook_espresso__EEM_Gateways__get_gateway_filepath__default_filepath',EVENT_ESPRESSO_GATEWAY_DIR . $gateway_name . DS . $filename,$gateway_name);
		if (file_exists($default_gateway_filepath)) {
			require_once($default_gateway_filepath);
		} else if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway_name . DS . $filename)) {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway_name . DS . $filename);
		} else {
			$msg = sprintf(
					__( 'The file : %s could not be located in either : %s or %s', 'event_espresso'), 
					'<b>' . $filename . '</b>',
					'<b>' . EVENT_ESPRESSO_GATEWAY_DIR . $gateway_name . DS . '</b>',
					'<b>' . EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway_name . DS . '</b>'
			);
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );	
		}
	}*/



	/**
	 * 		send_invoice
	 * 		@access public
	* 		@param	int	$id
	 * 		@return 	boolean	TRUE on success FALSE on fail
	 */
	public function send_invoice($id) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		$gateway = 'Invoice';
		$classname = 'EE_' . $gateway;
		$filename = $classname . '.class.php';
		if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . $filename)) {
			require_once(EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . $filename);
		} else if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway . DS . $filename)) {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway . DS . $filename);
		} else {
			$msg = sprintf(
					__( 'The file : %s could not be located in either : %s or %s', 'event_espresso'), 
					'<b>' . $filename . '</b>',
					'<b>' . EVENT_ESPRESSO_GATEWAY_DIR . $gateway . DS . '</b>',
					'<b>' . EVENT_ESPRESSO_PLUGINFULLPATH . 'gateways' . DS . $gateway . DS . '</b>'
			);
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );	
		}

		if (class_exists($classname)) {
			$that = &$this;
			$invoice = call_user_func(array($classname, 'instance'), $that);
			$invoice->send_invoice($id);
			return TRUE;
		} else {
			return FALSE;
		}
	}




	/**
	 * 		process_gateway_selection()
	 * 		@access public
	 * 		@return 	mixed	array on success or FALSE on fail
	 */
	public function process_gateway_selection() {	
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		// check for off site payment
		if ( isset( $_POST['selected_gateway'] ) && ! empty( $_POST['selected_gateway'] )) {
			$this->set_selected_gateway(sanitize_text_field( $_POST['selected_gateway'] ));
		} else {
			$msg =  __( 'Please select a method of payment in order to continue.', 'event_espresso' );
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
			return FALSE;
		}
		$this->_gateway_instances[ $this->_selected_gateway ]->process_gateway_selection();		
	}



	/**
	 * 		set_billing_info_for_confirmation
	 * 		@access public
	* 		@param	array	$billing_info
	 * 		@return 	mixed	array on success or FALSE on fail
	 */
	public function set_billing_info_for_confirmation( $billing_info = FALSE ) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if( ! is_array( $billing_info )) {
			return FALSE;
		}
		$confirm_info = $this->_gateway_instances[ $this->_selected_gateway ]->set_billing_info_for_confirmation( $billing_info );
		//$confirm_info['gateway'] = $this->display_name();
		$confirm_info[ __('payment method', 'event_espresso') ] = $this->display_name();
		return $confirm_info;
	}



	/**
	 * 		process_reg_step_3
	 * 		@access public
	* 		@param	int	$id
	 * 		@return 	mixed	void or FALSE on fail
	 */
	public function process_reg_step_3() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		$return_page_url = $this->_get_return_page_url();
		// free event?
		if (isset($_POST['reg-page-no-payment-required']) && absint($_POST['reg-page-no-payment-required']) == 1) {
			// becuz this was a free event we need to generate some pseudo gateway results
			$txn_results = array(
					'approved' => TRUE,
					'response_msg' => __('You\'re registration has been completed successfully.', 'event_espresso'),
					'status' => 'Approved',
					'details' => 'free event',
					'amount' => 0.00,
					'method' => 'none'
			);
			global $EE_Session;
			$EE_Session->set_session_data(array('txn_results' => $txn_results), 'session_data');
			$response = array(
					'msg' => array('success'=>TRUE),
					'forward_url' => $return_page_url
			);

		} else {
			$response = array(
					'msg' => $this->_gateway_instances[ $this->_selected_gateway ]->process_reg_step_3(),
					'forward_url' => $return_page_url
				);
		}
		return $response;
	}	



	/**
	 * 		get_return_page_url
	 *
	 * 		@access 		public
	 * 		@return 		void
	 */
	private function _get_return_page_url() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		global $org_options,$EE_Session;
		$session_data=$EE_Session->get_session_data();
		$a_current_registration=current($session_data['registration']);
		
		$return_page_id = $org_options['return_url'];
		// get permalink for thank you page
		// to ensure that it ends with a trailing slash, first we remove it (in case it is there) then add it again
		return add_query_arg(array('e_reg_url_link'=>$a_current_registration->reg_url_link()),
				rtrim( get_permalink( $return_page_id ), '/' ));
	}



	/**
	 * Replaces all but the last for digits with x's in the given credit card number
	 * @param int|string $cc The credit card number to mask
	 * @return string The masked credit card number
	 */
	function MaskCreditCard($cc){
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		// Get the cc Length
		$cc_length = strlen($cc);
		// Replace all characters of credit card except the last four and dashes
		for($i=0; $i<$cc_length-4; $i++){
			if($cc[$i] == '-'){continue;}
			$cc[$i] = 'X';
		}
		// Return the masked Credit Card #
		return $cc;
	}
	
	
	
	/**
	 * Add dashes to a credit card number.
	 * @param int|string $cc The credit card number to format with dashes.
	 * @return string The credit card with dashes.
	 */
	function FormatCreditCard($cc) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		// Clean out extra data that might be in the cc
		$cc = str_replace(array('-',' '),'',$cc);
		// Get the CC Length
		$cc_length = strlen($cc);
		// Initialize the new credit card to contian the last four digits
		$newCreditCard = substr($cc,-4);
		// Walk backwards through the credit card number and add a dash after every fourth digit
		for($i=$cc_length-5;$i>=0;$i--){
			// If on the fourth character add a dash
			if((($i+1)-$cc_length)%4 == 0){
				$newCreditCard = '&nbsp;'.$newCreditCard;
			}
			// Add the current character to the new credit card
			$newCreditCard = $cc[$i].$newCreditCard;
		}
		// Return the formatted credit card number
		return $newCreditCard;
	}


	/**
	 * Handles the thank_you_page, given the curren transaction
	 * @param type $transaction
	 */
	public function thank_you_page_logic(EE_Transaction $transaction) {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($this->_selected_gateway)
						&& !empty($this->_gateway_instances[ $this->_selected_gateway ])) {
			$this->_gateway_instances[ $this->_selected_gateway ]->thank_you_page_logic($transaction);
		}
		$this->check_for_completed_transaction($transaction);
	}
	
	/**
	 * Gets the HTML from the currently-selected gateway to display the payment's overview, specific to this gateway.
	 * Content to be shown may include errors, notes about the payment, and further payment instructions (often the case for offline gateways).
	 * 
	 * @param string gateway name (usually the folder's name where the main gateway file is located inside the gateways folder), eg Paypal_Standard
	 * @param EE_Transaction $transaction
	 * @return string of HTML to be displayed above the payment overview, usually on the thank you page
	 */
	public function get_payment_overview_content($gateway_name, EE_Payment $payment){
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($gateway_name)
						&& !empty($this->_gateway_instances[ $gateway_name ])) {
				ob_start();
				$this->_gateway_instances[ $gateway_name ]->get_payment_overview_content($payment);
				$output = ob_get_clean();
				return $output;
		}
		return '';
	}
	/**
	 * Checks if teh provided transaction is completed. If so, clears teh session and handles
	 * the logic of finalizing the transaction
	 * @param EE_Transaction $transaction
	 */
	public function check_for_completed_transaction(EE_Transaction $transaction){
		//throw new Exception("unfinished. This functino should check for a completed transaction .If completed, clear some session etc
		require_once('EEM_Transaction.model.php');
		if($transaction->status_ID() == EEM_Transaction::complete_status_code){
			$this->reset_session_data();
		}
	}
	
	/**
	 * Uses teh currently-active and selected gateway to handle an Instant Payment Notification.
	 * Obviously, if this occurs the active gateway must be an Offsite gateway
	 * @param EE_Transaction or ID $transaction Transaction to beudpated by the IPN
	 * @return boolean success
	 */
	public function handle_ipn_for_transaction($transaction){
		global $org_options;
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		$current_gateway=(!empty($this->_selected_gateway))?$this->_gateway_instances[$this->_selected_gateway] : null;
		//echo "eemgateway, handle ipn for transaction";
		//echo "current gawtay:";var_dump($current_gateway);
		if(!empty($current_gateway)	&& $current_gateway instanceof EE_Offsite_Gateway){
			
			
			$current_gateway->handle_ipn_for_transaction($transaction);
			//echo "current gateway in debug mode:";var_dump($current_gateway->debug_mode_active());
			if($current_gateway->debug_mode_active()){		
				//echo "in dbeug mode! send eamil! recipient:".$org_options['contact_email'];
				$debug_output=$current_gateway->get_debug_log();
				$success=wp_mail($org_options['contact_email'],"Event Espresso IPN Debug info for ".$this->_selected_gateway,"POST data received:".print_r($_POST,true)." output is".$debug_output);
				//echo "successful sent email?".$success;
			}
			
		}
	}



	/**
	 * 		get_country_ISO2_codes
	 * 		@access public
	 * 		@return 	array
	 */
	public function get_country_ISO2_codes() {
	
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		return array(
					'US' => 'United States',
					'AU' => 'Australia',
					'CA' => 'Canada', 
					'GB' => 'United Kingdom',
					'FR' => 'France', 
					'IT' => 'Italy', 
					'ES' => 'Spain', 
					'DE' => 'Germany', 
					'CH' => 'Switzerland', 
					'NL' => 'The Netherlands', 
					'SE' => 'Sweden', 
					'AF' => 'Afghanistan', 
					'AL' => 'Albania', 
					'CY' => 'Akrotiri and Dhekelia', 
					'AD' => 'Andorra', 
					'AO' => 'Angola', 
					'AI' => 'Anguilla', 
					'AQ' => 'Antarctica', 
					'AG' => 'Antigua and Barbuda', 
					'SA' => 'Saudi Arabia', 
					'DZ' => 'Argelia', 
					'AR' => 'Argentina', 
					'AM' => 'Armenia', 
					'AW' => 'Aruba', 
					'AT' => 'Austria', 
					'AZ' => 'Azerbaijan', 
					'BS' => 'Bahamas', 
					'BH' => 'Bahrein', 
					'BD' => 'Bangladesh', 
					'BB' => 'Barbados', 
					'BE' => 'Belgium ', 
					'BZ' => 'Belize', 
					'BJ' => 'Benin', 
					'BM' => 'Bermudas', 
					'BY' => 'Belarus', 
					'BO' => 'Bolivia', 
					'BA' => 'Bosnia and Herzegovina', 
					'BW' => 'Botswana', 
					'BV' => 'Bouvet Island', 
					'BR' => 'Brazil', 
					'BN' => 'Brunei', 
					'BG' => 'Bulgaria', 
					'BF' => 'Burkina Faso', 
					'BI' => 'Burundi', 
					'BT' => 'Bhutan', 
					'CV' => 'Cape Verde', 
					'KH' => 'Cambodia', 
					'CM' => 'Cameroon', 
					'KY' => 'Cayman Islands', 
					'CF' => 'Central African Republic', 
					'TD' => 'Chad', 
					'CL' => 'Chile', 
					'CN' => 'China', 
					'CX' => 'Christmas Island', 
					'CY' => 'Cyprus', 
					'CC' => 'Cocos Island', 
					'CK' => 'Cook Islands', 
					'CO' => 'Colombia', 
					'KM' => 'Comoros', 
					'CG' => 'Congo', 
					'KP' => 'Corea del Norte', 
					'CR' => 'Costa Rica', 
					'HR' => 'Croatia', 
					'CU' => 'Cuba', 
					'CZ' => 'Czech Republic', 
					'DK' => 'Danmark', 
					'DJ' => 'Djibouti', 
					'DM' => 'Dominica', 
					'DO' => 'Dominican Republic', 
					'EC' => 'Ecuador', 
					'EG' => 'Egypt', 
					'SV' => 'El Salvador', 
					'ER' => 'Eritrea', 
					'SK' => 'Eslovakia', 
					'SI' => 'Eslovenia', 
					'EE' => 'Estonia', 
					'ET' => 'Ethiopia', 
					'FO' => 'Faroe islands', 
					'FK' => 'Falkland Islands', 
					'FJ' => 'Fiji', 
					'FI' => 'Finland', 
					'GA' => 'Gabon', 
					'GM' => 'Gambia', 
					'GE' => 'Georgia', 
					'GH' => 'Ghana', 
					'GI' => 'Gibraltar', 
					'GR' => 'Greece', 
					'GD' => 'Grenada', 
					'GL' => 'Greenland', 
					'GP' => 'Guadeloupe', 
					'GU' => 'Guam', 
					'GT' => 'Guatemala', 
					'GN' => 'Guinea', 
					'GQ' => 'Equatorial Guinea',
					'GW' => 'Guinea-Bissau', 
					'GY' => 'Guyana', 
					'HT' => 'Haiti', 
					'HN' => 'Honduras', 
					'HK' => 'Hong Kong', 
					'HU' => 'Hungary', 
					'IN' => 'India', 
					'IO' => 'British Indian Ocean Territory', 
					'ID' => 'Indonesia', 
					'IQ' => 'Iraq', 
					'IR' => 'Iran', 
					'IE' => 'Ireland', 
					'IS' => 'Iceland', 
					'IL' => 'Israel', 
					'CI' => 'Ivory Coast ', 
					'JM' => 'Jamaica', 
					'JP' => 'Japan', 
					'JO' => 'Jordan', 
					'KZ' => 'Kazakhstan', 
					'KE' => 'Kenya', 
					'KG' => 'Kirguistan', 
					'KI' => 'Kiribati', 
					'KR' => 'South Korea', 
					'XK' => 'Kosovo', 
					'KW' => 'Kuwait', 
					'LA' => 'Laos', 
					'LV' => 'Latvia', 
					'LS' => 'Lesotho', 
					'LB' => 'Lebanon', 
					'LR' => 'Liberia', 
					'LY' => 'Libya', 
					'LI' => 'Liechtenstein', 
					'LT' => 'Lithuania', 
					'LU' => 'Luxemburg', 
					'MO' => 'Macao', 
					'MK' => 'Macedonia', 
					'MG' => 'Madagascar', 
					'MY' => 'Malaysia', 
					'MW' => 'Malawi', 
					'MV' => 'Maldivas', 
					'ML' => 'Mali', 
					'MT' => 'Malta', 
					'MP' => 'Northern Marianas', 
					'MA' => 'Marruecos', 
					'MH' => 'Marshall islands', 
					'MQ' => 'Martinica', 
					'MU' => 'Mauricio', 
					'MR' => 'Mauritania', 
					'YT' => 'Mayote', 
					'MX' => 'Mexico', 
					'FM' => 'Micronesia', 
					'MD' => 'Moldova', 
					'MC' => 'Monaco', 
					'MN' => 'Mongolia', 
					'MS' => 'Montserrat', 
					'ME' => 'Montenegro', 
					'MZ' => 'Mozambique', 
					'MM' => 'Myanmar', 
					'NA' => 'Namibia', 
					'NR' => 'Nauru', 
					'NP' => 'Nepal', 
					'AN' => 'Netherlands Antilles', 
					'NI' => 'Nicaragua', 
					'NE' => 'Niger', 
					'NG' => 'Nigeria', 
					'NU' => 'Niue', 
					'NO' => 'Norway', 
					'NC' => 'New Caledonia', 
					'NZ' => 'New Zealand', 
					'OM' => 'Oman', 
					'PK' => 'Pakistan', 
					'PW' => 'Palau', 
					'PA' => 'Panama', 
					'PG' => 'Papua New Guinea', 
					'PY' => 'Paraguay', 
					'PE' => 'Peru', 
					'PH' => 'Philippines', 
					'PL' => 'Poland', 
					'PT' => 'Portugal', 
					'PR' => 'Puerto Rico', 
					'QA' => 'Qatar', 
					'RW' => 'Rowanda', 
					'RO' => 'Romania', 
					'RU' => 'Russia', 
					'PM' => 'Saint Pierre and Miquelon', 
					'WS' => 'Samoa', 
					'AS' => 'American Samoa', 
					'SM' => 'San Marino', 
					'VC' => 'San Vincente y las Granadinas', 
					'SH' => 'Santa Helena', 
					'LC' => 'Santa Lucia', 
					'SN'  => 'Senegal', 
					'SC' => 'Seychelles', 
					'SL' => 'Sierra Leona', 
					'SG' => 'Singapore', 
					'SY' => 'Syria', 
					'SO' => 'Somalia', 
					'LK' => 'Sri Lanka', 
					'ZA' => 'South Africa', 
					'SD' => 'Sudan', 
					'SR' => 'Suriname', 
					'SZ' => 'Swaziland', 
					'TH' => 'Thailand', 
					'TW' => 'Taiwan', 
					'TZ' => 'Tanzania', 
					'TJ' => 'Tajikistan', 
					'TP' => 'Timor Oriental', 
					'TG' => 'Togo', 
					'TK' => 'Tokelau', 
					'TO' => 'Tonga', 
					'TT' => 'Trinidad and Tobago', 
					'TN' => 'Tunisia', 
					'TM' => 'Turkmenistan', 
					'TR' => 'Turkey', 
					'TV' => 'Tuvalu', 
					'UA' => 'Ukraine', 
					'UG' => 'Uganda', 
					'AE' => 'United Arab Emirates', 
					'UY' => 'Uruguay', 
					'UZ' => 'Uzbekistan', 
					'VU' => 'Vanuatu', 
					'VA' => 'Vatican City', 
					'VE' => 'Venezuela', 
					'VN' => 'Vietnam', 
					'VI' => 'Virgin Islands',
					'YE' => 'Yemen', 
					'YU' => 'Yugoslavia', 
					'ZM' => 'Zambia', 
					'ZW' => 'Zimbabwe'					
				);
	
	}

		

	
}
