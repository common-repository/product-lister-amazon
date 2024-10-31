<?php
require_once ('../../../../wp-blog-header.php');

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 *
 * @class    Class_marketplace_cron
 * @version  1.0.0
 * @category Class
 * @author   CedCommerce
 */

class Class_marketplace_cron{

	public function __construct(){
		do_action('ced_umb_amazon_cron_job');
	}
}
$marketplace_cron_obj =	new Class_marketplace_cron();
?>