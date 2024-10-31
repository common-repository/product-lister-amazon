<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * main class for handling product update request
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/marketplaces/amazon
 */

class ced_umb_amazon_Amazon_Product_Update {

	public $errorTrackArray = array();
	public $isAllRequiredValuePresent = true;

	public $amazon_xml_lib;

	public $profile_data = array();
	public $isProfileAssignedToProduct = false;
	

	/**
	 * Constructor.
	 *
	 * registering actions and hooks for amazon.
	 *
	 * @since 1.0.0
	 */
	
	public function __construct() {
		
	}
}	