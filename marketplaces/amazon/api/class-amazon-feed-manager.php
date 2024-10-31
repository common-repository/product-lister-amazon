<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * main class for handling amazon order reqests.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/marketplaces/amazon
 */

if( !class_exists( 'ced_umb_amazon_Amazon_Feed_Manager' ) ) :


class ced_umb_amazon_Amazon_Feed_Manager {

	public $xmlStringForShipping = '';
	public $amazon_xml_lib;
	public $amazon_lib;
	
	/**
	 * Constructor.
	 *
	 * registering actions and hooks for amazon.
	 *
	 * @since 1.0.0
	 */
	
	public function __construct()
	{
		require_once 'class-amazon-xml-lib.php';
		$this->amazon_xml_lib = new ced_umb_amazon_Amazon_XML_Lib();
		
		/**for Shistation automation**/
		
	}
	
	
	function getFeedItemsStatus( $feedId, $includeDetails="true", $limit=50 ) 
	{
		$directorypath = plugin_dir_path(__FILE__);
		if(!class_exists("AmazonFeedResult"))
		{
			require($directorypath.'../lib/amazon/includes/classes.php');
		}
		$this->amazon_lib = new AmazonFeedResult();
		$this->amazon_lib->setFeedId($feedId);
		$response = $this->amazon_lib->fetchFeedResult(true);
		return $response;
	}
	
	
}
endif;
?>