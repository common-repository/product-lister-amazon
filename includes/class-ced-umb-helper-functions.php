<?php
/**
 * The file that defines the global helper functions using throughout the plugin.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/includes
 */
class ced_umb_amazon_Helper {
	
	/**
	 * The instance of ced_umb_amazon_Helper.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static $_instance;
	
	/**
	 * ced_umb_amazon_Helper Instance.
	 *
	 * Ensures only one instance of ced_umb_amazon_Helper is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return ced_umb_amazon_Helper - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * print notices.
	 * 
	 * @since 1.0.0
	 */
	public function umb_print_notices($notices=array()){
		if(count($notices)){
			foreach($notices as $notice_array){

				$message = isset($notice_array['message']) ? esc_html($notice_array['message']) : '';
				$classes = isset($notice_array['classes']) ? esc_attr($notice_array['classes']) : 'error is-dismissable';
				if(!empty($message)){ ?>
					 <div class="<?php echo $classes;?>">
					 	<p><?php echo $message;?></p>
					 </div>
				<?php 	
				}
			}
		}
	}
	
	/**
	 * get conditional product id.
	 * 
	 * @since 1.0.0
	 */
	public function umb_get_product_by($params){
		global $wpdb;

		$where = '';
		if(count($params)){
			$Flag = false;
			foreach($params as $meta_key=>$meta_value){
				if(!empty($meta_value) && !empty($meta_key)){
					if(!$Flag){
						$where .= 'meta_key="'.sanitize_key($meta_key).'" AND meta_value="'.$meta_value.'"';
						$Flag = true;
					}else{
						$where .= ' OR meta_key="'.sanitize_key($meta_key).'" AND meta_value="'.$meta_value.'"';
					}
				}
			}
			if($Flag){
				$product_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE $where LIMIT 1" );
				if($product_id)
					return $product_id;
			}
		}
		return false;
	}
	
	/**
	 * writing logs.
	 *
	 * @since 1.0.0
	 * @param string $filename
	 * @param string $stringTowrite
	 */
	public function umb_write_logs($filename, $stringTowrite)
	{
		$dirTowriteFile = ced_umb_amazon_LOG_DIRECTORY;
		if(defined("ced_umb_amazon_LOG_DIRECTORY"))
		{
			if(!is_dir($dirTowriteFile))
			{
				if(!mkdir($dirTowriteFile,0755))
				{
					return;
				}
			}
			$fileTowrite = $dirTowriteFile."/$filename";
			if(!$fp = fopen($fileTowrite, "a"))
			{
				return;
			}
			$fr = fwrite($fp,$stringTowrite."\n");
			fclose($fp);
		}
		else {
			return;
		}
	}
	
	/**
	 * get profile details,
	 * 
	 * @since 1.0.0
	 */
	public function ced_umb_profile_details( $params=array() ){
		global $wpdb;
		$profile_name = "";
		if(isset($params['id'])){
			$id = $params['id'];
			$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
			$tablename = $prefix.'profiles';
			$profile_name = $wpdb->get_var("SELECT `name` FROM `$tablename` WHERE `id` = '$id' AND `active` = 1");
		}
		return $profile_name;
	}
	
}
?>