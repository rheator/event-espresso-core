<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link					http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * Price Type Model
 *
 * @package			Event Espresso
 * @subpackage		includes/models/
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
require_once ( EVENT_ESPRESSO_INCLUDES_DIR . 'models/EEM_Custom_Table_Base.model.php' );

class EEM_Price_Type extends EEM_Custom_Table_Base {

	// private instance of the Price Type object
	private static $_instance = NULL;
	// An array of the price type objects
	public $type = NULL;

	/**
	*	Price Base types
	*
	*	@access	private
	*	@var int
	*/
	public static $base_types = array( 1 => 'Event Price', 2 => 'Discount', 3 => 'Surcharge', 4 => 'Tax' );




	/**
	 * 		private constructor to prevent direct creation
	 * 		@Constructor
	 * 		@access protected
	 * 		@return void
	 */
	protected function __construct() {
		global $wpdb;
		// set table name
		$this->table_name = $wpdb->prefix . 'esp_price_type';
		// set item names
		$this->singlular_item = __('Price Type','event_espresso');
		$this->plural_item = __('Price Types','event_espresso');		
		// array representation of the price type table and the data types for each field
		$this->table_data_types = array(
				'PRT_ID' 					=> '%d',
				'PRT_name' 			=> '%s',
				'PBT_ID' 					=> '%d',
				'PRT_is_member' 	=> '%d',
				'PRT_is_discount' 	=> '%d',
				'PRT_is_tax' 			=> '%d',
				'PRT_is_percent' 	=> '%d',
				'PRT_is_global' 		=> '%d',
				'PRT_order' 			=> '%d',
				'PRT_deleted'			=> '%d'
		);
		// load Price_Type object class file
		require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Price_Type.class.php');

		$this->type = $this->get_all_price_types();

	}





	/**
	 * 		This funtion is a singleton method used to instantiate the EEM_Attendee object
	 *
	 * 		@access public
	 * 		@return EEM_Price_Type instance
	 */
	public static function instance() {

		// check if instance of EEM_Price_Type already exists
		if (self::$_instance === NULL) {
			// instantiate Price_Type model
			self::$_instance = new self();
		}
		// EEM_Price_Type object
		return self::$_instance;
	}





	/**
	 * 		cycle though array of price types and create objects out of each item
	 *
	 * 		@access		private
	 * 		@param		array		$price_types
	 * 		@return 	mixed		array on success, FALSE on fail
	 */
	private function _create_objects($price_types = FALSE) {

		if (!$price_types) {
			return FALSE;
		}

		foreach ($price_types as $price_type) {
			$array_of_objects[$price_type->PRT_ID] = new EE_Price_Type(
											$price_type->PRT_name,
											$price_type->PBT_ID,
											$price_type->PRT_is_member,
											$price_type->PRT_is_percent,
											$price_type->PRT_is_global,
											$price_type->PRT_order,
											$price_type->PRT_deleted,
											$price_type->PRT_ID
			);
		}
		return $array_of_objects;
	}





	/**
	 * 		instantiate a new price type object with blank/empty properties
	 *
	 * 		@access		public
	 * 		@return		mixed		array on success, FALSE on fail
	 */
	public function get_new_price_type() {
		return new EE_Price_Type( '', 1, FALSE, FALSE, FALSE, 0, FALSE );
	}





	/**
	 * 	retreive  ALL price types from db
	 *
	 * 	@access		public
	 * 	@return 		string		$orderby				sorting column
	 * 	@return 		string		$order					sort ASC or DESC ?
	 * 	@return 		array		$limit					query limit and offset
	 * 	@return 		boolean	$output				return count or results ?
	 * 	@return 		boolean	$trashed				return deleted records or just active non-deleted ones ?
	 * 	@return		mixed		array on success, FALSE on fail
	 */
	public function get_all_price_types( $orderby='PRT_order', $order='ASC', $limit = array(0,10), $output = 'OBJECT_K' , $trashed = FALSE ) {
		
		$output = $output === TRUE ? 'COUNT' :  'OBJECT_K';
		
		// retreive all price types
		$price_types = $this->select_all_where ( 
				array( 'PRT_deleted' => $trashed ),
				$orderby, 
				$order, 
				'=', 
				$limit,
				$output
			);

//		echo '<h1>XXXXXXXX  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h1>';
//		echo '<h4>$orderby : ' . $orderby . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$order : ' . $order . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		printr( $limit, '$limit  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//		echo '<h4>$output : ' . $output . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$trashed : ' . $trashed . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		 printr( $price_types, '$price_types  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

		// bad results ?
		if ( empty( $price_types ) || $price_types === FALSE || is_wp_error( $price_types )) {
			return FALSE;
		}			

		//  return the count OR create objects out of data
		$price_types = $output == 'COUNT' ? $price_types : $this->_create_objects($price_types);
		return $price_types;

		
	}





	/**
	 * 		retreive  a single price type from db via it's ID
	 *
	 * 		@access		public
	 * 		@param		$PRT_ID
	 * 		@return		mixed		array on success, FALSE on fail
	 */
	public function get_price_type_by_ID($PRT_ID = FALSE) {

		if (!$PRT_ID) {
			return FALSE;
		}
		// retreive a particular price
		$where_cols_n_values = array('PRT_ID' => $PRT_ID);
		if ($price_type = $this->select_row_where($where_cols_n_values)) {
			$price_type_array = $this->_create_objects(array($price_type));
			return array_shift($price_type_array);
		} else {
			return FALSE;
		}
	}





	/**
	 * 		retreive a single price type from db via it's column values
	 *
	 * 	@access		public
	 * 	@param		array
	 * 		@return 	mixed		array on success, FALSE on fail
	 */
	public function get_price_type($where_cols_n_values = FALSE) {

		if (!$where_cols_n_values) {
			return FALSE;
		}

		if ($price_type = $this->select_row_where($where_cols_n_values)) {
			$price_type_array = $this->_create_objects(array($price_type));
			return array_shift($price_type_array);
		} else {
			return FALSE;
		}
	}





	/**
	 * 		This function inserts table data
	 *
	 * 		@access public
	 * 		@param array $set_column_values - array of column names and values for the SQL INSERT
	 * 		@return array
	 */
	public function insert($set_column_values) {
		// grab data types from above and pass everything to espresso_model (parent model) to perform the update
		$results = $this->_insert($this->table_name, $this->table_data_types, $set_column_values);
		$this->type = $this->get_all_price_types();
		return $results;
	}





	/**
	 * 		This function updates table data
	 *
	 * 		@access public
	 * 		@param array $set_column_values - array of column names and values for the SQL SET clause
	 * 		@param array $where_cols_n_values - column names and values for the SQL WHERE clause
	 * 		@return array
	 */
	public function update($set_column_values, $where_cols_n_values) {
//		echo printr( $set_column_values, '$set_column_values' );
//		echo printr( $where_cols_n_values, '$where_cols_n_values' );
		// grab data types from above and pass everything to espresso_model (parent model) to perform the update
		return $this->_update($this->table_name, $this->table_data_types, $set_column_values, $where_cols_n_values);
	}





	public function delete_by_id($ID) {
		if (!$ID) {
			return FALSE;
		}
		require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'models/EEM_Price.model.php');
		$PRC = EEM_Price::instance();
		//check if any prices use this price type
		if ( $prices = $PRC->get_all_prices_that_are_type($ID)) {
			$msg = __('The Price Type could not be deleted because there are existing Prices that currently use this Price Type.  If you still wish to delete this Price Type, then either delete those Prices or change them to use other Price Types.', 'event_espresso');
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ ); 
			return FALSE;
		} 
		
		if ( $this->delete(array('PRT_ID' => $ID))) {
			return TRUE;
		} else {
			return FALSE;
		}

	}






}

// End of file EEM_Price_Type.model.php
// Location: /ee-mvc/models/EEM_Price_Type.model.php