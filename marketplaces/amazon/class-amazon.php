<?php
/**
 * MarketPlace: Amazon
 *
 */
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * main class for handling reqests.
 *
 * @since      1.0.0
 *
 * @package    Amazon Product Lister
 * @subpackage amazon-product-lister/marketplaces/amazon
 */

if( !class_exists( 'ced_umb_amazon_amazon_manager' ) ) :

/**
 * single product related functionality.
*
* Manage all single product related functionality required for listing product on marketplaces.
*
* @since      1.0.0
* @package    Amazon Product Lister
* @subpackage amazon-product-lister/marketplaces/amazon
* @author     CedCommerce <cedcommerce.com>
*/
class ced_umb_amazon_amazon_manager{
	/**
	 * The Instace of ced_umb_amazon_amazon_Manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      $_instance   The Instance of ced_umb_amazon_amazon_Manager class.
	 */
	private static $_instance;
	
	/**
	 * Instance of ced_umb_amazon_amazon_api.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     $_api    Instance of ced_umb_amazon_amazon_api class.
	 */
	private $_api;
	private $inventoryXMLString = '';
	private $isRunInventoryUpdate = false;
	
	public $amazon_lib;
	public $amazon_xml_lib;
	public $amazon_inventory_update;
	public $amazon_order;
	public $amazon_feed_manager;
	public $amazon_product_update;
	public $profile_data = array();
	public $isProfileAssignedToProduct = false;
	
	/**
	 * ced_umb_amazon_amazon_Manager Instance.
	 *
	 * Ensures only one instance of ced_umb_amazon_amazon_Manager is loaded or can be loaded.
	 *
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 * @static
	 * @return ced_umb_amazon_amazon_Manager instance.
	 */
	public static function get_instance() 
	{
		if ( is_null( self::$_instance ) ) 
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public $marketplaceID = 'amazon';
	public $marketplaceName = 'Amazon';
	
	/**
	 * Constructor.
	 *
	 * registering actions and hooks for amazon.
	 *
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	public function __construct() 
	{
		require_once 'api/class-amazon-xml-lib.php';
		$this->amazon_xml_lib = new ced_umb_amazon_amazon_XML_Lib();
		
		require_once 'api/class-amazon-feed-manager.php';
		$this->amazon_feed_manager = new ced_umb_amazon_Amazon_Feed_Manager();
		
		require_once 'api/class-amazon-product-update.php';
		$this->amazon_product_update = new ced_umb_amazon_Amazon_Product_Update();
		
		add_filter( 'ced_umb_amazon_add_new_available_marketplaces' , array( $this, 'ced_umb_amazon_add_new_available_marketplaces' ), 10, 1 );
		add_filter( 'ced_umb_amazon_render_marketplace_configuration_settings' , array( $this, 'ced_umb_amazon_render_marketplace_configuration_settings' ), 10, 2 );
		
		
		add_action( 'ced_umb_amazon_save_marketplace_configuration_settings' , array( $this, 'ced_umb_amazon_save_marketplace_configuration_settings'), 10, 2 );
		add_action( 'ced_umb_amazon_validate_marketplace_configuration_settings' , array( $this, 'ced_umb_amazon_validate_marketplace_configuration_settings'), 10, 2 );
		add_action( 'admin_enqueue_scripts',array($this,'load_amazon_scripts'));
		add_action( 'wp_ajax_updateamazonCategoriesInDB', array($this,'updateamazonCategoriesInDB'));
		add_filter( 'ced_umb_amazon_required_product_fields', array( $this, 'add_amazon_required_fields' ), 11, 2 );
		add_action( 'ced_umb_amazon_product_page_render', array($this,'ced_umb_amazon_amazon_product_page_render'), 10, 2);
		add_action( 'wp_ajax_fetch_amazon_attribute_for_selected_category', array($this,'fetch_amazon_attribute_for_selected_category'));
		add_action( 'wp_ajax_fetch_amazon_category_product_type', array($this,'fetch_amazon_category_product_type'));
		add_action( 'wp_ajax_fetch_amazon_category_product_type_profile', array($this,'fetch_amazon_category_product_type_profile'));
		add_action( 'wp_ajax_ced_umb_amazon_cron_job', array($this,'ced_umb_amazon_cron_amazon_process'));
		
		add_action( 'ced_umb_amazon_required_fields_process_meta_simple', array($this,'ced_umb_amazon_required_fields_process_meta_simple'), 11, 1 );
		add_action( 'ced_umb_amazon_render_marketplace_feed_details', array( $this, 'ced_umb_amazon_render_marketplace_feed_details'), 10, 2 );
		
		
		
		add_action('wp_ajax_fetch_amazon_attribute_for_selected_category_for_profile_section', array($this,'fetch_amazon_attribute_for_selected_category_for_profile_section'));
		add_filter( 'umb_save_additional_profile_info', array( $this, 'umb_save_additional_profile_info' ), 11, 1 );
		
// 		wp_schedule_event(time(), 'hourly', 'ced_umb_amazon_cron_job');
	}
	
	/**
	 * Save Profile Information
	 *
	 * @name umb_save_additional_profile_info
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	
	function umb_save_additional_profile_info( $profile_data )
	{	
		$marketPlace = 'ced_umb_amazon_'.$this->marketplaceName;
		if(isset($_POST[$marketPlace])) {
			foreach ($_POST[$marketPlace] as $key ) {
				if(isset($_POST[$key])) {
					$fieldid = isset($key) ? $key : '';
					$fieldvalue = isset($_POST[$key]) ? $_POST[$key][0] : null;
					$fieldattributemeta = isset($_POST[$key.'_attibuteMeta']) ? $_POST[$key.'_attibuteMeta'] : null;
					$profile_data[$fieldid] = array('default'=>$fieldvalue,'metakey'=>$fieldattributemeta);
				}
			}
		}
		return $profile_data;
	}

	/**
	 * Fetch selected category attribute for profile
	 *
	 * @name fetch_amazon_attribute_for_selected_category_for_profile_section
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	function fetch_amazon_attribute_for_selected_category_for_profile_section() 
	{
		$marketPlace = 'ced_umb_amazon_'.$this->marketplaceName;
		if(isset($_POST['profileID'])) 
		{
			$profileid = $_POST['profileID'];
		}
		
		global $wpdb,$global_ced_umb_amazon_Render_Attributes;
		$table_name = $wpdb->prefix.ced_umb_amazon_PREFIX.'profiles';
		$profile_data = array();
		if($profileid)
		{
			$query = "SELECT * FROM `$table_name` WHERE `id`=$profileid";
			$profile_data = $wpdb->get_results($query,'ARRAY_A');
			if(is_array($profile_data)) 
			{
				$profile_data = isset($profile_data[0]) ? $profile_data[0] : $profile_data;
				$profile_data = isset($profile_data['profile_data']) ? json_decode($profile_data['profile_data'],true) : array();
			}
		}
		/* select dropdown setup */
		$attributes		=	wc_get_attribute_taxonomies();
		$attrOptions	=	array();
		$addedMetaKeys = get_option('CedUmbProfileSelectedMetaKeys', false);

		if($addedMetaKeys && count($addedMetaKeys) > 0)
		{
			foreach ($addedMetaKeys as $metaKey)
			{
				$attrOptions[$metaKey]	=	$metaKey;
			}
		}
		if(!empty($attributes))
		{
			foreach($attributes as $attributesObject)
			{
				$attrOptions['umb_pattr_'.$attributesObject->attribute_name]	=	$attributesObject->attribute_label;
			}
		}
		/* select dropdown setup */
		
		$productID = $_POST['productID'];
		$categoryID = $_POST['categoryID'];
		
		$indexToUse = '0';
		if(isset($_POST['indexToUse'])) {
			$indexToUse = $_POST['indexToUse'];
		}
		
		$selectDropdownHTML= renderMetaSelectionDropdownOnProfilePageamazon();
		
		$amazonJsonFileName = 'amazon-category.json';
		$amazoncategory = $this->amazon_xml_lib->readamazonInfoFromJsonFile( $amazonJsonFileName );
		if( !isset($amazoncategory[$categoryID]) ) 
		{
			echo '<h3>'._e('No Category Selected Yet!','ced-amazon-lister').'</h3>';
		}
		else 
		{
			$amazoncatagories = $amazoncategory[$categoryID]['value'];
			foreach($amazoncatagories as $amazoncatagorieselement)
			{
				
				
				if ($amazoncatagorieselement ['name'] == 'ProductType') 
				{
					$catproducttype = array ();
					$amazoncatagoryproductypeelements = $amazoncatagorieselement ['value'];
					foreach ( $amazoncatagoryproductypeelements as $amazoncatagoryproductypeelement ) 
					{
						$catproducttypename = $amazoncatagoryproductypeelement ['name'];
						$catproducttype [$catproducttypename] = $catproducttypename;
					}

					$attributeNameToRender = $amazoncatagorieselement ['name'];
					$attributeID = $productID . '_ced_umb_amazon_amazon_' . $categoryID . '_' . $name;
						
					$fielddata = isset($profile_data[$categoryID.'_'.$attributeID]) ? $profile_data[$categoryID.'_'.$attributeID] : array();
					
					$valueForDropdown = $catproducttype;
					$default = isset($fielddata['default']) ? $fielddata['default'] : null;
					$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
					$fieldDescription = "";
					$conditionally_required = "";
					$conditionally_required_text = "";

					?>
					
					<div class="ced_umb_amazon_amazon_product_type">
					<?php
					echo '<td>';
						$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML( $attributeID,$attributeNameToRender,$valueForDropdown,$categoryID,$productID,$marketPlace,$fieldDescription,$indexToUse,array('case'=>'profile','value'=>$default),$conditionally_required,$conditionally_required_text);
					echo '</td>';
					
					?>
					</div>
					<div class="ced_umb_amazon_amazon_product_type_wrapper">
					
					</div>
					<?php
				}
				else
				{ 
					if(isset($amazoncatagorieselement['value']))
					{
						if (count($amazoncatagorieselement['value']) == count($amazoncatagorieselement['value'], COUNT_RECURSIVE))
						{
							$name = $amazoncatagorieselement['name'];
							$nameid = $name;
							$valueForDropdown = $amazoncatagorieselement['value'];
							echo "<div class='ced_amazon_attribute_wrapper'>";
							$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$name,$valueForDropdown,$categoryID,$productID,$marketPlace,"","");
							$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
							$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
							echo $updatedDropdownHTML;
							echo "</div>";
						}
						else
						{
							$amazoncatagorieselementmultiples = $amazoncatagorieselement['value'];
								
							foreach($amazoncatagorieselementmultiples as $amazoncatagorieselementmultiple)
							{
								if(isset($amazoncatagorieselementmultiple['value']))
								{
									if (count($amazoncatagorieselementmultiple['value']) == count($amazoncatagorieselementmultiple['value'], COUNT_RECURSIVE))
									{
										$name = $amazoncatagorieselement['name'];
										$subname = $amazoncatagorieselementmultiple['name'];
										$nameid = $name.'_'.$subname;
										
										$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
										$default = isset($fielddata['default']) ? $fielddata['default'] : null;
										$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
										
										$valueForDropdown = $amazoncatagorieselementmultiple['value'];
										$subname = $name.'-'.$subname;
										echo "<div class='ced_amazon_attribute_wrapper'>";
										$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
										$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
										$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
										echo $updatedDropdownHTML;
										echo "</div>";
									}
									else
									{
										$amazoncatagorieselementalues = $amazoncatagorieselementmultiple['value'];
				
										foreach($amazoncatagorieselementalues as $amazoncatagorieselementalue)
										{
											if(isset($amazoncatagorieselementalue['value']))
											{
												$name = $amazoncatagorieselement['name'];
												$subname = $amazoncatagorieselementmultiple['name'];
												$subsubname = $amazoncatagorieselementalue['name'];
												$nameid = $name.'_'.$subname.'_'.$subsubname;
												
												$fielddata = isset($profile_data[$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
												$default = isset($fielddata['default']) ? $fielddata['default'] : null;
												$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
												
												$subsubname = $subname.'-'.$subsubname;
												$valueForDropdown = $amazoncatagorieselementalue['value'];
												echo "<div class='ced_amazon_attribute_wrapper'>";
												$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
												$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
												$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
												echo $updatedDropdownHTML;
												echo "</div>";
											}
											else
											{
												$name = $amazoncatagorieselement['name'];
												$subname = $amazoncatagorieselementmultiple['name'];
												$subsubname = $amazoncatagorieselementalue['name'];
												$nameid = $name.'_'.$subname.'_'.$subsubname;
												
												$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
												$default = isset($fielddata['default']) ? $fielddata['default'] : null;
												$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
												
												
												$subsubname = $subname.'-'.$subsubname;
				
												if(isset($amazoncatagorieselementalue['type']))
												{
													if($amazoncatagorieselementalue['type'] == 'boolean')
													{
														$valueForDropdown = array('1'=>'True', '0'=>'False');
														echo "<div class='ced_amazon_attribute_wrapper'>";
														$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
														$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
														$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
														echo $updatedDropdownHTML;
														echo "</div>";
													}
													elseif($amazoncatagorieselementalue['type'] == 'positiveInteger' || $amazoncatagorieselementalue['type'] == 'nonNegativeInteger' || $amazoncatagorieselementalue['type'] == 'PositiveDimension' || $amazoncatagorieselementalue['type'] =='Dimension')
													{
														echo "<div class='ced_amazon_attribute_wrapper'>";
														$this->renderInputNumberHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
														$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
														$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
														echo $updatedDropdownHTML;
														echo "</div>";
													}
													else
													{
														echo "<div class='ced_amazon_attribute_wrapper'>";
														$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
														$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
														$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
														echo $updatedDropdownHTML;
														echo "</div>";
													}
												}
												else
												{
													echo "<div class='ced_amazon_attribute_wrapper'>";
													$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
													$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
													$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
													echo $updatedDropdownHTML;
													echo "</div>";
												}
											}
										}
									}
								}
								else
								{
									$name = $amazoncatagorieselement['name'];
									$subname = $amazoncatagorieselementmultiple['name'];
									$nameid = $name.'_'.$subname;
									
									$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
									$default = isset($fielddata['default']) ? $fielddata['default'] : null;
									$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
									
									
									$valueForDropdown = $amazoncatagorieselement['value'];
									$subname = $name.'-'.$subname;
									if(isset($amazoncatagorieselementmultiple['type']))
									{
										if($amazoncatagorieselementmultiple['type'] == 'boolean')
										{
											$valueForDropdown = array('1'=>'True', '0'=>'False');
											echo "<div class='ced_amazon_attribute_wrapper'>";
											$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
											$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
											$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
											echo $updatedDropdownHTML;
											echo "</div>";
										}
										elseif($amazoncatagorieselementmultiple['type'] == 'positiveInteger' || $amazoncatagorieselementmultiple['type'] == 'nonNegativeInteger' || $amazoncatagorieselementmultiple['type'] == 'PositiveDimension' || $amazoncatagorieselementmultiple['type'] =='Dimension')
										{
											echo "<div class='ced_amazon_attribute_wrapper'>";
											$this->renderInputNumberHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
											$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
											$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
											echo $updatedDropdownHTML;
											echo "</div>";
										}
										else
										{
											echo "<div class='ced_amazon_attribute_wrapper'>";
											$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
											$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
											$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
											echo $updatedDropdownHTML;
											echo "</div>";
										}
									}
									else
									{
										echo "<div class='ced_amazon_attribute_wrapper'>";
										$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
										$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
										$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
										echo $updatedDropdownHTML;
										echo "</div>";
									}
								}
							}
						}
					}
					else
					{
						$name = $amazoncatagorieselement['name'];
						$nameid = $name;
						
						$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
						$default = isset($fielddata['default']) ? $fielddata['default'] : null;
						$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
						if(isset($amazoncatagorieselement['type']))
						{
							if($amazoncatagorieselement['type'] == 'boolean')
							{
								$valueForDropdown = array('1'=>'True', '0'=>'False');
								echo "<div class='ced_amazon_attribute_wrapper'>";
								$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$name,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
								$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
								$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
								echo $updatedDropdownHTML;
								echo "</div>";
							}
							elseif($amazoncatagorieselement['type'] == 'positiveInteger' || $amazoncatagorieselement['type'] == 'nonNegativeInteger' || $amazoncatagorieselement['type'] == 'PositiveDimension' || $amazoncatagorieselement['type'] =='Dimension')
							{
								echo "<div class='ced_amazon_attribute_wrapper'>";
								$this->renderInputNumberHTML($nameid,$name,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
								$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
								$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
								echo $updatedDropdownHTML;
								echo "</div>";
							}
							else
							{
								echo "<div class='ced_amazon_attribute_wrapper'>";
								$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$name,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
								$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
								$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
								echo $updatedDropdownHTML;
								echo "</div>";
							}
						}
						else
						{
							echo "<div class='ced_amazon_attribute_wrapper'>";
							$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$name,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
							$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
							$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
							echo $updatedDropdownHTML;
							echo "</div>";
						}
					}
				}
			}
		}
		
		$indexToUse = '0';
		if (isset ( $_POST ['indexToUse'] )) 
		{
			$indexToUse = $_POST['indexToUse'];
		}
		$categoryFound = false;
		echo '<div class="ced_umb_amazon_cmn active">';
		
		$selectDropdownHTML= renderMetaSelectionDropdownOnProfilePageamazon();
		
		foreach ($categories as $key => $category) 
		{
			
		}
			
		echo '</div>';
	
		/* render framework specific fields */
		$pFieldInstance = ced_umb_amazon_product_fields::get_instance();
		$framework_specific =$pFieldInstance->get_custom_fields('framework_specific',false);
		
		if(is_array($framework_specific) && is_array($framework_specific['amazon'])) 
		{
			$attributesList = $framework_specific['amazon'];
			?>
			<div class="ced_umb_amazon_cmn">
				<table class="wp-list-table widefat fixed striped">
					<tbody>
					</tbody>
					<tbody>
						<?php
						global $global_ced_umb_amazon_Render_Attributes;
						$marketPlace = "ced_umb_amazon_required_common";
						$productID = 0;
						$categoryID = '';
						$indexToUse = 0;
						$selectDropdownHTML= renderMetaSelectionDropdownOnProfilePageamazon();
						foreach ($attributesList as $value) 
						{
							$isText = true;
							$field_id = trim($value['fields']['id'],'_');
							$default = isset($profile_data[$value['fields']['id']]) ? $profile_data[$value['fields']['id']] : '';
							$default = $default['default'];
							echo '<tr>';
							echo '<td>';
							if( $value['type'] == "_select" ) {
								$valueForDropdown = $value['fields']['options'];
								$tempValueForDropdown = array();
								foreach ($valueForDropdown as $key => $value) {
									$tempValueForDropdown[$value] = $value;
								}
								$valueForDropdown = $tempValueForDropdown;
								$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($field_id,ucfirst($value['fields']['label']),$valueForDropdown,$categoryID,$productID,$marketPlace,$value['fields']['description'],$indexToUse,array('case'=>'profile','value'=>$default));
								$isText = false;
							}
							else {
								$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($field_id,ucfirst($value['fields']['label']),$categoryID,$productID,$marketPlace,$value['fields']['description'],$indexToUse,array('case'=>'profile','value'=>$default));
							}
							echo '</td>';
							echo '<td>';
							if($isText) {
								$previousSelectedValue = 'null';
								if( isset($profile_data[$value['fields']['id']]) && $profile_data[$value['fields']['id']] != 'null') {
									$previousSelectedValue = $profile_data[$value['fields']['id']]['metakey'];
								}
								$updatedDropdownHTML = str_replace('{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML);
								$updatedDropdownHTML = str_replace('value="'.$previousSelectedValue.'"', 'value="'.$previousSelectedValue.'" selected="selected"', $updatedDropdownHTML);
								echo $updatedDropdownHTML;
							}
							echo '</td>';
							echo '</tr>';
						}	
						?>
					</tbody>
					<tfoot>
					</tfoot>
				</table>
			</div>
			<?php
		}
		
		wp_die();
	}
	
	/**
	 * Add new marketplace .
	 *
	 * @name ced_umb_amazon_add_new_available_marketplaces
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	function ced_umb_amazon_add_new_available_marketplaces( $availableMarketPlace )
	{
		$availableMarketPlace[] =array(
				'id' => $this->marketplaceID,
				'name' => $this->marketplaceName,
				'status' => (getMarketPlaceStatusamazon( $this->marketplaceID )) ? '<b style="color:green">'._e('Enable','ced-amazon-lister').'</b>': '<b style="color:red">'._e('Disbale','ced-amazon-lister').'</b>',
				'validate' => (isMarketPlaceConfigurationsValidatedamazon( $this->marketplaceID )) ?  '<b style="color:green">'.__('Validated','ced-amazon-lister').'</b>' : '<b style="color:red">'._e('Need Validation','ced-amazon-lister').'</b>'
		);
	
		return $availableMarketPlace;
	}
	
	/**
	 * Marketplace Configuration Setting
	 *
	 * @name ced_umb_amazon_render_marketplace_configuration_settings
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	function ced_umb_amazon_render_marketplace_configuration_settings( $configSettings, $marketplaceID )
	{
		if( $marketplaceID != $this->marketplaceID )
		{
			return $configSettings;
		}
		else
		{
			$configSettings=array();
			$saved_amazon_details = get_option( 'ced_umb_amazon_amazon_configuration', true );
			$service_url = isset( $saved_amazon_details['service_url'] ) ? esc_attr( $saved_amazon_details['service_url'] ) : '';
			$marketplace_id = isset( $saved_amazon_details['marketplace_id'] ) ? esc_attr( $saved_amazon_details['marketplace_id'] ) : '';
			$merchant_id = isset( $saved_amazon_details['merchant_id'] ) ?  $saved_amazon_details['merchant_id']  : "";
			$key_id = isset( $saved_amazon_details['key_id'] ) ? esc_attr( $saved_amazon_details['key_id'] ) : '';
			$secret_key = isset( $saved_amazon_details['secret_key'] ) ? esc_attr( $saved_amazon_details['secret_key'] ) : '';
			$auth_token = isset( $saved_amazon_details['auth_token'] ) ?  $saved_amazon_details['auth_token']  : "";
	
			$configSettings['configSettings'] = array(
					'ced_umb_amazon_amazon_service_url' => array(
							'name' => __('Amazon MWS Endpoint','ced-amazon-lister'),
							'type' => 'text',
							'value' => $service_url
					),
					'ced_umb_amazon_amazon_marketplace_id' => array(
							'name' => __('MarketPlace Id','ced-amazon-lister'),
							'type' => 'text',
							'value' => $marketplace_id
					),
					'ced_umb_amazon_amazon_merchant_id' => array(
							'name' => __('Merchant Id','ced-amazon-lister'),
							'type' => 'text',
							'value' => $merchant_id
					),
					'ced_umb_amazon_amazon_key_id' => array(
							'name' => __('Amazon Key','ced-amazon-lister'),
							'type' => 'text',
							'value' => $key_id
					),
					'ced_umb_amazon_amazon_secret_key' => array(
							'name' => __('Secret Key','ced-amazon-lister'),
							'type' => 'text',
							'value' => $secret_key
					),
	
					'ced_umb_amazon_amazon_auth_token' => array(
							'name' => __('Amazon Auth Token','ced-amazon-lister'),
							'type' => 'text',
							'value' => $auth_token
					)
			);
	
			$configSettings['showUpdateButton'] = isMarketPlaceConfigurationsValidatedamazon( $this->marketplaceID );
			$configSettings['marketPlaceName'] = $this->marketplaceName;
			return $configSettings;
		}
	}
	
	/**
	 * Save Marketplace Configuration Setting
	 *
	 * @name ced_umb_amazon_save_marketplace_configuration_settings
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	
	function ced_umb_amazon_save_marketplace_configuration_settings( $configSettingsToSave, $marketplaceID )
	{
		global $cedumbamazonhelper;
		if( $marketplaceID == $this->marketplaceID )
		{
			$amazon_service_url = isset($configSettingsToSave['ced_umb_amazon_amazon_service_url']) ? sanitize_text_field( $configSettingsToSave['ced_umb_amazon_amazon_service_url'] ) : '';
			$amazon_marketplace_id = isset($configSettingsToSave['ced_umb_amazon_amazon_marketplace_id']) ? sanitize_text_field( $configSettingsToSave['ced_umb_amazon_amazon_marketplace_id'] ) : '';
			$amazon_merchant_id = isset($configSettingsToSave['ced_umb_amazon_amazon_merchant_id']) ? sanitize_text_field( $configSettingsToSave['ced_umb_amazon_amazon_merchant_id'] ) : '';
			$amazon_key_id = isset($configSettingsToSave['ced_umb_amazon_amazon_key_id']) ? sanitize_text_field( $configSettingsToSave['ced_umb_amazon_amazon_key_id'] ) : '';
			$amazon_secret_key = isset($configSettingsToSave['ced_umb_amazon_amazon_secret_key']) ? sanitize_text_field( $configSettingsToSave['ced_umb_amazon_amazon_secret_key'] ) : '';
			$amazon_auth_token = isset($configSettingsToSave['ced_umb_amazon_amazon_auth_token']) ? sanitize_text_field( $configSettingsToSave['ced_umb_amazon_amazon_auth_token'] ) : '';
	
				
			if($amazon_service_url && $amazon_marketplace_id && $amazon_merchant_id && $amazon_key_id && $amazon_secret_key && $amazon_auth_token)
			{
				$amazon_configuration = array();
				$amazon_configuration['service_url'] = $amazon_service_url;
				$amazon_configuration['marketplace_id'] = $amazon_marketplace_id;
				$amazon_configuration['merchant_id'] = $amazon_merchant_id;
				$amazon_configuration['key_id'] = $amazon_key_id;
				$amazon_configuration['secret_key'] = $amazon_secret_key;
				$amazon_configuration['auth_token'] = $amazon_auth_token;
				update_option( 'ced_umb_amazon_amazon_configuration', $amazon_configuration );
				$notice['message'] = __('Credentials saved successfully','ced-amazon-lister');
				$notice['classes'] = "notice notice-success";
				$validation_notice[] = $notice;
				$cedumbamazonhelper->umb_print_notices($validation_notice);
				unset($validation_notice);
			}
			else
			{
				$notice['message'] = __('Fields can\'t be blank','ced-amazon-lister');
				$notice['classes'] = "notice notice-error";
				$validation_notice[] = $notice;
				$cedumbamazonhelper->umb_print_notices($validation_notice);
				unset($validation_notice);
			}
			update_option("ced_umb_amazon_save_".$this->marketplaceID,"yes");
			update_option("ced_umb_amazon_validate_".$this->marketplaceID,"no");
		}
	}
	

	/**
	 * Validate Marketplace Configuration Setting
	 *
	 * @name ced_umb_amazon_validate_marketplace_configuration_settings
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	
	public function ced_umb_amazon_validate_marketplace_configuration_settings( $configSettingsToSave, $marketplaceID )
	{
		global $cedumbamazonhelper;
		try
		{
			if( $marketplaceID == $this->marketplaceID )
			{
				delete_option('ced_umb_amazon_validate_'.$this->marketplaceID);
				$saved_amazon_details = get_option( 'ced_umb_amazon_amazon_configuration', true );
				$amazon_service_url = isset($saved_amazon_details['service_url']) ? sanitize_text_field( $saved_amazon_details['service_url'] ) : '';
				$amazon_marketplace_id = isset($saved_amazon_details['marketplace_id']) ? sanitize_text_field( $saved_amazon_details['marketplace_id'] ) : '';
				$amazon_merchant_id = isset($saved_amazon_details['merchant_id']) ? sanitize_text_field( $saved_amazon_details['merchant_id'] ) : '';
				$amazon_key_id = isset($saved_amazon_details['key_id']) ? sanitize_text_field( $saved_amazon_details['key_id'] ) : '';
				$amazon_secret_key = isset($saved_amazon_details['secret_key']) ? sanitize_text_field( $saved_amazon_details['secret_key'] ) : '';
				$amazon_auth_token = isset($saved_amazon_details['auth_token']) ? sanitize_text_field( $saved_amazon_details['auth_token'] ) : '';
					
				if($amazon_service_url && $amazon_marketplace_id && $amazon_merchant_id && $amazon_key_id && $amazon_secret_key && $amazon_auth_token)
				{
					$directorypath = plugin_dir_path(__FILE__);
					require($directorypath.'/lib/amazon/includes/classes.php');
	
					$this->amazon_lib = new AmazonFeedList();
					$this->amazon_lib->setFeedStatuses(array( "_IN_PROGRESS_", "_SUBMITTED_"));
					$this->amazon_lib->fetchFeedSubmissions(); //this is what actually sends the request
					$list = $this->amazon_lib->getFeedList();
						
					if(isset($list) && is_array($list))
					{
						update_option('ced_umb_amazon_validate_'.$this->marketplaceID,"yes");
						update_option('ced_umb_amazon_enabled_marketplaces',array('amazon'));
						$notice['message'] = __('Configuration setting is Validated Successfully','ced-amazon-lister');
						$notice['classes'] = "notice notice-success";
						$validation_notice[] = $notice;
						$cedumbamazonhelper->umb_print_notices($validation_notice);
					}
				}
				else
				{
					$notice['message'] = __('Consumer Id and Private Key can\'t be blank','ced-amazon-lister');
					$notice['classes'] = "notice notice-error";
					$validation_notice[] = $notice;
					$cedumbamazonhelper->umb_print_notices($validation_notice);
					unset($validation_notice);
				}
			}
		}
		catch(Exception $e)
		{
			echo $message = $e->getMessage();
			$param['action'] = __('API CREDENTIAL VALIDATION','ced-amazon-lister');
			$param['issue'] = __('API Cerdentials is not valid. Please check again. Issue is :','ced-amazon-lister').$message;
			$notice['message'] = __('API Cerdentials is not valid. Please check again.','ced-amazon-lister','ced-amazon-lister');
			$notice['classes'] = "notice notice-error";
			$validation_notice[] = $notice;
			$cedumbamazonhelper->umb_print_notices($validation_notice);
			unset($validation_notice);
		}
	}
	
	/**
	 * Load amazon scripts.
	 *
	 * @name load_amazon_scripts
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	public function load_amazon_scripts()
	{
		$screen    = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		if ( in_array( $screen_id, array( 'product' ) ) ) {
			wp_enqueue_script( 'ced_umb_amazon_amazon_edit_datatable', plugin_dir_url( __FILE__ ) . 'js/jquery.dataTables.min.js',array( 'jquery' ), time(), true);
			wp_register_script( 'ced_umb_amazon_amazon_edit_product', plugin_dir_url( __FILE__ ) . 'js/product-edit.js',array( 'jquery' ), time(), true);
			global $post;
			wp_localize_script( 'ced_umb_amazon_amazon_edit_product', 'ced_umb_amazon_amazon_edit_product_script_AJAX', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'product_id' => $post->ID
			));
			wp_enqueue_script('ced_umb_amazon_amazon_edit_product');
		}
		
		//category mapping page.
		if( $screen_id == 'amazon_page_umb-amazon-cat-map' ){
			wp_register_script( 'ced_umb_amazon_amazon_cat_mapping', plugin_dir_url( __FILE__ ) . 'js/category_mapping.js', array( 'jquery' ), time(), true );
			$localization_params = array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
			);
			wp_localize_script( 'ced_umb_amazon_amazon_cat_mapping', 'umb_amazon_cat_map', $localization_params );
			wp_enqueue_script('ced_umb_amazon_amazon_cat_mapping');
		}
	
		
		/* manage things on edit profile page js */
		if( $screen_id == 'amazon_page_umb-amazon-profile' && isset($_GET['action'])){
	
			wp_register_script( 'ced_umb_amazon_amazon_profile_edit', plugin_dir_url( __FILE__ ) . 'js/profile-edit.js',array( 'jquery' ), time(), true);
			wp_localize_script( 'ced_umb_amazon_amazon_profile_edit', 'ced_umb_amazon_amazon_edit_profile_AJAX', array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
			));
			wp_enqueue_script('ced_umb_amazon_amazon_profile_edit');
		}
	}
	
	/**
	 * Update amazon categories
	 *
	 * @name updateamazonCategoriesInDB
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	function updateamazonCategoriesInDB()
	{
		if(isset($_POST['categoryID']) && isset($_POST['actionToDo']) )
		{
			$categoryID =  $_POST['categoryID'];
			$actionToDo =  $_POST['actionToDo'];
			$selectedamazonCategories = get_option('ced_umb_amazon_selected_amazon_categories');
			$newamazonCategories = array();
			if(isset($selectedamazonCategories) && !empty($selectedamazonCategories))
			{
				$selectedamazonCategories = json_decode($selectedamazonCategories,TRUE);
				$newamazonCategories = $selectedamazonCategories;
			}
			if($actionToDo == 'delete')
			{
				if(array_key_exists($categoryID,$newamazonCategories))
				{
					unset($newamazonCategories[$categoryID]);
				}
			}
			else if($actionToDo == 'append')
			{
				if(!array_key_exists($categoryID,$newamazonCategories))
				{
					$newamazonCategories[$categoryID] =  $_POST['categoryNAME'];
				}
			}
			$newamazonCategories = json_encode($newamazonCategories);
			update_option( 'ced_umb_amazon_selected_amazon_categories', $newamazonCategories );
		}
		wp_die();
	}
	

	/**
	 * amazon required fields.
	 *
	 * @name add_amazon_required_fields
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	public function add_amazon_required_fields($fields=array(),$post='')
	{
		$postId = isset($post->ID) ? intval($post->ID) : 0;
		$selectedamazonCategories = get_option('ced_umb_amazon_selected_amazon_categories');
		if(isset($selectedamazonCategories) && !empty($selectedamazonCategories)) {
			$selectedamazonCategories = json_decode($selectedamazonCategories,TRUE);
		}
	
		if(is_array($selectedamazonCategories))
		{
			array_unshift($selectedamazonCategories,"-- Select --");
		}
	
		$name = 'Amazon Fields';
		$field['id'] = "_umb_amazon_$name";
		$field['type'] = '_wrapper_heading';
		$field['fields']['id'] = "_umb_amazon_$name";
		$field['fields']['label'] = "$name";
		$field['fields']['description'] = "";
			
		$fields[] = $field;
	
		$name = "FulfillmentLatency";
		$field['id'] = "_umb_amazon_$name";
		$field['type'] = '_text_input';
		$field['fields']['id'] = "_umb_amazon_$name";
		$field['fields']['description'] = "";
		$name = "Amazon FulfillmentLatency";
		$name .= '<span class="ced_umb_amazon_wal_required">'.__('[ Required ]','ced-amazon-lister').'</span>';
		$field['fields']['label'] = "$name";
			
		$fields[] = $field;
	
		$marketplace = $this->marketplaceID;
	
		$amazondatayPath = 'json/amazon-product.json';
		$amazondatayPath = ced_umb_amazon_DIRPATH.'marketplaces/'.$marketplace.'/partials/'.$amazondatayPath;
		ob_start();
		readfile($amazondatayPath);
		$json_data = ob_get_clean();
		$productdatas = json_decode($json_data, TRUE);
	
		foreach($productdatas as $productdata)
		{
			if($productdata['name'] == "SKU" || $productdata['name'] == "PromoTag" || $productdata['name'] == "ShippedByFreight" || $productdata['name'] == "Amazon-Vendor-Only" || $productdata['name'] == "Amazon-Only" || $productdata['name'] == "RegisteredParameter" || $productdata['name'] == "EnhancedImageURL" || $productdata['name'] == "DiscoveryData" || $productdata['name'] == "StandardProductID" || $productdata['name'] == "RelatedProductID" ||$productdata['name'] == "GtinExemptionReason" ||$productdata['name'] == "DiscontinueDate" || $productdata['name'] == "ExternalProductUrl" || $productdata['name'] == "OffAmazonChannel" || $productdata['name'] == "OnAmazonChannel" || $productdata['name'] == "LiquidVolume" || $productdata['name'] == "LaunchDate" || $productdata['name'] == "ReleaseDate" || $productdata['name'] == "Rebate" || $productdata['name'] == "Designer" || $productdata['name'] == "ProductTaxCode")// || $productdata['name'] == "Condition"
			{
				continue;
			}
				
			if($productdata['name'] != 'ProductData')
			{
				if(!empty($productdata['value']))
				{
					$descriptiondatavalues = $productdata['value'];
					$field = array();
					if (count($descriptiondatavalues) == count($descriptiondatavalues, COUNT_RECURSIVE))
					{
						if(!empty($productdata['ref']))
						{
							$name = $productdata['ref'];
						}
						else
						{
							$name = $productdata['name'];
						}
	
						if(!empty($productdata['value']))
						{
							$field['type'] = '_select';
							$field['fields']['options'] = $this->add_blank_element_on_array($descriptiondatavalues);
						}
						else
						{
							$field['type'] = '_text_input';
						}

						$field['id'] = "_umb_amazon_$name";
						$field['fields']['id'] = "_umb_amazon_$name";
						$field['fields']['description'] = "";
	
						$name .= '<span class="ced_umb_amazon_wal_required"> '.__('[ Required ]','ced-amazon-lister').'</span>';
	
						$field['fields']['label'] = "$name";
							
						$fields[] = $field;
					}
					else
					{
						$parentname = $productdata['name'];
						$name = $productdata['name'];
						$field['id'] = "_umb_amazon_$name";
						$field['type'] = '_heading';
						$field['fields']['id'] = "_umb_amazon_$name";
						$field['fields']['label'] = "$name";
						$field['fields']['description'] = "";
						
						$fields[] = $field;
	
						foreach($descriptiondatavalues as $descriptiondatavalue)
						{
							$field = array();
							if(isset($descriptiondatavalue['value']))
							{
	
								if (count($descriptiondatavalue['value']) == count($descriptiondatavalue['value'], COUNT_RECURSIVE))
								{
									if($name == "IsDiscontinuedByManufacturer" || $name == "DeliveryScheduleGroupID" || $name == "DeliveryChannel" || $name == "PurchasingChannel" || $name == "MaxAggregateShipQuantity" || $name == "CustomizableTemplateName" || $name == "FEDAS_ID" || $name == "TSDAgeWarning" || $name == "TSDWarning" || $name == "TSDLanguage" || $name == "OptionalPaymentTypeExclusion" || $name == "Title" || $name == "Brand" || $name == "Description" || $name == "Manufacturer" || $name == "MfrPartNumber" || $name == "Designer" || $name == "UsedFor" || $name == "OtherItemAttributes" || $name == "TargetAudience" || $name == "SubjectContent" || $name == "IsGiftWrapAvailable" || $name == "SerialNumberRequired" || $name == "Prop65" || $name == "CPSIAWarning" || $name == "Memorabilia" || $name == "SerialNumberRequired")
									{
										continue;
									}
										
									$name = $descriptiondatavalue['name'];
									$field['id'] = "_umb_amazon_$parentname"."_"."$name";
									$field['type'] = '_select';
									$field['fields']['id'] = "_umb_amazon_$parentname"."_"."$name";
									$name .= '<span class="ced_umb_amazon_wal_required"> '.__('[ Required ]','ced-amazon-lister').'</span>';
									$field['fields']['label'] = "$name";
									$field['fields']['options'] = $this->add_blank_element_on_array($descriptiondatavalue['value']);
									$field['fields']['description'] = "";
									$fields[] = $field;
								}
								else
								{
									$name =  $descriptiondatavalue['name'];
									if($name == "MSRPWithTax" || $name == "PackageWeight" || $name == "ItemDimensions" || $name == "PackageDimensions" || $name == "ShippingWeight" || $name == "MSRP" || $name == "SerialNumberRequired" || $name == "Prop65" || $name == "CPSIAWarning" || $name == "Memorabilia" || $name == "SerialNumberRequired")
									{
										continue;
									}
									$field['id'] = "_umb_amazon_$name";
									$field['type'] = '_heading';
									$field['fields']['id'] = "_umb_amazon_$name";
									$field['fields']['label'] = "$name";
									$field['fields']['description'] = "";
									$fields[] = $field;
										
									$multidatavalues = $descriptiondatavalue['value'];
										
									foreach($multidatavalues as $multidatavalue)
									{
										$field = array();
										if(isset($multidatavalue['value']))
										{
											if (count($multidatavalue['value']) == count($multidatavalue['value'], COUNT_RECURSIVE))
											{
												$field = array();
												$name = $multidatavalue['name'];
												$childname = $descriptiondatavalue['name'];
												$field['id'] = "_umb_amazon_$parentname"."_".$childname."_".$name;
												$field['type'] = '_select';
												$field['fields']['id'] = "_umb_amazon_$parentname"."_".$childname."_".$name;
												$name .= '<span class="ced_umb_amazon_wal_required">  '.__('[ Required ]','ced-amazon-lister').'</span>';
												$field['fields']['label'] = "$name";
												$field['fields']['options'] = $this->add_blank_element_on_array($multidatavalue['value']);
												$field['fields']['description'] = "";
												$fields[] = $field;
											}
											else
											{
												$field = array();
												$name =  $multidatavalue['name'];
												$field['id'] = "_umb_amazon_$name";
												$field['type'] = '_heading';
												$field['fields']['id'] = "_umb_amazon_$name";
												$field['fields']['label'] = "$name";
												$field['fields']['description'] = "";
												
												$fields[] = $field;
												$submultidatavalues = $multidatavalue['value'];
												foreach($submultidatavalues as $submultidatavalue)
												{
													$field = array();
													if(isset($submultidatavalue['value']))
													{
														$subname = $submultidatavalue['name'];
														$childname = $descriptiondatavalue['name'];
														$field['id'] = "_umb_amazon_$parentname"."_".$childname."_".$name."_".$subname;
														$field['type'] = '_select';
														$field['fields']['id'] = "_umb_amazon_$parentname"."_".$childname."_".$name."_".$subname;
														$subname .= '<span class="ced_umb_amazon_wal_required">'.__('[ Required ]','ced-amazon-lister').'</span>';
														$field['fields']['label'] = "$subname";
														$field['fields']['options'] = $this->add_blank_element_on_array($submultidatavalue['value']);
														$field['fields']['description'] = "";
														$fields[] = $field;
													}
													else
													{
														$subname = $submultidatavalue['name'];
														$childname = $descriptiondatavalue['name'];
														$field['id'] = "_umb_amazon_$parentname"."_".$childname."_".$name."_".$subname;
														$field['type'] = '_text_input';
														$field['fields']['id'] = "_umb_amazon_$parentname"."_".$childname."_".$name."_".$subname;
														$field['fields']['description'] = "";
														$subname .= '<span class="ced_umb_amazon_wal_required">'.__('[ Required ]','ced-amazon-lister').'</span>';
														$field['fields']['label'] = "$subname";
	
														if(isset($descriptiondatavalue['type']))
														{
															if($descriptiondatavalue['type'] == 'dateTime')
															{
																$field['fields']['class'] = "ced_umb_amazon_amazon_datetime";
															}
														}
														if(isset($descriptiondatavalue['type']))
														{
															if($descriptiondatavalue['type'] == 'boolean')
															{
																$field['type'] = '_select';
																$field['fields']['options'] = $this->add_blank_element_on_array(array('0'=>'True','1'=>'False'));
															}
														}
														if(isset($descriptiondatavalue['type']))
														{
															if (strpos($descriptiondatavalue['type'], 'positive') !== false)
															{
																$field['fields']['class'] = "wc_input_price";
															}
														}
	
														if($submultidatavalue['name'] == 'Dimension')
														{
															$field['fields']['class'] = "wc_input_price";
														}
	
														$fields[] = $field;
													}
												}
											}
										}
										else
										{
											$name = $multidatavalue['name'];
											$childname = $descriptiondatavalue['name'];
											$field['id'] = "_umb_amazon_$parentname"."_".$childname."_".$name;
											$field['type'] = '_text_input';
											$field['fields']['id'] = "_umb_amazon_$parentname"."_".$childname."_".$name;
											$name .= '<span class="ced_umb_amazon_wal_required">'.__('[ Required ]','ced-amazon-lister').'</span>';
											$field['fields']['label'] = "$name";
											$field['fields']['description'] = "";
											
											if(isset($descriptiondatavalue['type']))
											{
												if($descriptiondatavalue['type'] == 'dateTime')
												{
													$field['fields']['class'] = "ced_umb_amazon_amazon_datetime";
												}
											}
												
											if(isset($descriptiondatavalue['type']))
											{
												if($descriptiondatavalue['type'] == 'boolean')
												{
													$field['type'] = '_select';
													$field['fields']['options'] =  $this->add_blank_element_on_array(array('0'=>'True','1'=>'False'));
												}
											}
											if(isset($descriptiondatavalue['type']))
											{
												if (strpos($descriptiondatavalue['type'], 'positive') !== false)
												{
													$field['fields']['class'] = "wc_input_price";
												}
											}
												
											if($multidatavalue['name'] == 'Dimension')
											{
												$field['fields']['class'] = "wc_input_price";
											}
												
											$fields[] = $field;
										}
									}
								}
	
							}
							else
							{
								$name = $descriptiondatavalue['name'];
								if($productdata['name'] == 'DescriptionData')
								{
									if($name == "PlatinumKeywords" || $name == "LegalDisclaimer" || $name == "CPSIAWarningDescription" || $name == "MerchantCatalogNumber" || $name == "MaxOrderQuantity" || $name == "IsDiscontinuedByManufacturer" || $name == "DeliveryScheduleGroupID" || $name == "DeliveryChannel" || $name == "PurchasingChannel" || $name == "MaxAggregateShipQuantity" || $name == "CustomizableTemplateName" || $name == "FEDAS_ID" || $name == "TSDAgeWarning" || $name == "TSDWarning" || $name == "TSDLanguage" || $name == "OptionalPaymentTypeExclusion" || $name == "Title" || $name == "Brand" || $name == "Description" || $name == "Manufacturer" || $name == "MfrPartNumber" || $name == "Designer" || $name == "UsedFor" || $name == "OtherItemAttributes" || $name == "TargetAudience" || $name == "SubjectContent" || $name == "IsGiftWrapAvailable" || $name == "IsGiftMessageAvailable" || $name == "PromotionKeywords" || $name == "IsCustomizable" || $name == "RecommendedBrowseNode" || $name == "SerialNumberRequired" || $name == "Prop65" || $name == "CPSIAWarning" || $name == "Memorabilia" || $name == "SerialNumberRequired" || $name == "Autographed")
									{
										continue;
									}
								}
								
								$field['id'] = "_umb_amazon_$parentname"."_"."$name";
								$field['type'] = '_text_input';
								$field['fields']['id'] = "_umb_amazon_$parentname"."_"."$name";
								
								if($name == "BulletPoint" || $name == "SearchTerms")
								{
									$field['fields']['desc_tip'] = true;
									$field['fields']['description'] = __( 'Please enter terms with "|" seperated.', 'ced-amazon-lister' );
								}
								
								$name .= '<span class="ced_umb_amazon_wal_required">'.__('[ Required ]','ced-amazon-lister').'</span>';
								$field['fields']['label'] = "$name";
								
								
	
								if(isset($descriptiondatavalue['type']))
								{
									if($descriptiondatavalue['type'] == 'dateTime')
									{
										$field['fields']['class'] = "ced_umb_amazon_amazon_datetime";
									}
								}
								if(isset($descriptiondatavalue['type']))
								{
									if($descriptiondatavalue['type'] == 'boolean')
									{
										$field['type'] = '_select';
										$field['fields']['options'] = $this->add_blank_element_on_array(array('0'=>'True','1'=>'False'));
									}
								}
	
								if($descriptiondatavalue['name'] == 'Dimension')
								{
									$field['fields']['class'] = "wc_input_price";
								}
	
								if(isset($descriptiondatavalue['type']))
								{
									if (strpos($descriptiondatavalue['type'], 'positive') !== false)
									{
										$field['fields']['class'] = "wc_input_price";
									}
								}
	
								$field['fields']['description'] = "";
								$fields[] = $field;
							}
						}
					}
				}
				else
				{
					$field = array();
					
					if(!empty($productdata['ref']))
					{
						$name = $productdata['ref'];
					}
					else
					{
						$name = $productdata['name'];
					}
						
					if(!empty($productdata['value']))
					{
						$field['type'] = '_select';
						$field['fields']['options'] =  $this->add_blank_element_on_array($productdata['value']);
					}
					else
					{
						$field['type'] = '_text_input';
					}
					
					$field['id'] = "_umb_amazon_$name";
					$field['fields']['id'] = "_umb_amazon_$name";
						
					$name .= '<span class="ced_umb_amazon_wal_required">  '.__('[ Required ]','ced-amazon-lister').'</span>';
						
					$field['fields']['label'] = "$name";
						
					if(isset($productdata['type']))
					{
						if($productdata['type'] == 'dateTime')
						{
							$field['fields']['class'] = "ced_umb_amazon_amazon_datetime";
						}
					}
						
					if(isset($productdata['type']))
					{
						if($productdata['type'] == 'boolean')
						{
							$field['type'] = '_select';
							$field['fields']['options'] = $this->add_blank_element_on_array(array('0'=>'True','1'=>'False'));
						}
					}
						
					if($productdata['name'] == 'Dimension')
					{
						$field['fields']['class'] = "wc_input_price";
					}
						
					if(isset($productdata['type']))
					{
						if (strpos($productdata['type'], 'positive') !== false)
						{
							$field['fields']['class'] = "wc_input_price";
						}
					}
					$field['fields']['description'] = "";
					$fields[] = $field;
				}
			}
		}
	
		$fields[] = array(
				'type' => '_select',
				'id' => '_umb_amazon_category',
				'fields' => array(
						'id' => '_umb_amazon_category',
						'class' => '_umb_amazon_category',
						'label' => __( 'Amazon Category <span class="ced_umb_amazon_wal_required"> '.__('[ Required ]','ced-amazon-lister').'</span>', 'ced-amazon-lister' ),
						'options' => $selectedamazonCategories,
						'desc_tip' => true,
						'description' => __( 'Identify the category specification. There is only one category can be used for any single item. NOTE: Once an item is created, this information cannot be updated.', 'ced-amazon-lister' )
				),
		);
		return $fields;
	}
	
	/**
	 * Heading for amazon fields
	 * @param unknown $type
	 * @param unknown $field
	 */
	function ced_umb_amazon_amazon_product_page_render($type, $field)
	{
		if($type == '_heading')
		{
			?>
<h2 class="parent_wrapper" style="background-color: #ccc;"><?php echo $field['fields']['label'];?></h2>
<?php 
		}

		if($type == '_wrapper_heading')
		{
			?>
<h3 class="parent_wrapper"
	style="background-color: rgb(204, 204, 204); padding: 12px;"><?php echo $field['fields']['label'];?></h3>
<?php 
		}
	}
	
	/**
	 * Fetch selected category attribute for product
	 *
	 * @name fetch_amazon_attribute_for_selected_category
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	
	function fetch_amazon_attribute_for_selected_category()
	{
		global $global_ced_umb_amazon_Render_Attributes;
		$productID = $_POST['productID'];
		$categoryID =$_POST['categoryID'];
		$marketPlace = 'ced_umb_amazon_'.$this->marketplaceName;
		$amazonJsonFileName = 'amazon-category.json';
		$amazoncategory = $this->amazon_xml_lib->readamazonInfoFromJsonFile( $amazonJsonFileName );
		$amazoncatagories = $amazoncategory[$categoryID]['value'];
		?>
		<div class="ced_umb_amazon_amazon_attribute_section">
			<div class="ced_umb_amazon_toggle_section">
				<div class="ced_umb_amazon_toggle">
					<span><?php _e('Amazon Attributes','ced-amazon-lister');?></span>
				</div>
				<div class="ced_umb_amazon_toggle_div ced_amazon_attr_wrapper">
					<?php 
					foreach($amazoncatagories as $amazoncatagorieselement)
					{
						if($amazoncatagorieselement['name'] == 'ProductType')
						{
							$catproducttype = array();
							$amazoncatagoryproductypeelements = $amazoncatagorieselement['value'];
							foreach ($amazoncatagoryproductypeelements as $amazoncatagoryproductypeelement)
							{
								$catproducttypename = $amazoncatagoryproductypeelement['name'];
								$catproducttype[$catproducttypename] = $catproducttypename;
							}
							
							$name = $amazoncatagorieselement['name'];
							$nameid = $productID.'_ced_umb_amazon_amazon_'.$categoryID.'_'.$name;
							$valueForDropdown = $catproducttype;
							
							?>
							<div class="ced_umb_amazon_amazon_product_type">
							<?php 
							$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$name,$valueForDropdown,$categoryID,$productID,$marketPlace,"","");
							?>
							</div>
							<div class="ced_umb_amazon_amazon_product_type_wrapper">
							<?php 
							
								$metakey = $categoryID."_".$productID."_ced_umb_amazon_amazon_".$categoryID."_ProductType";
								$producttype = get_post_meta($productID, $metakey, true);
								if(isset($producttype) && !empty($producttype))
								{
									$marketPlace = 'ced_umb_amazon_'.$this->marketplaceName;
									$amazonJsonFileName = 'amazon-category.json';
									$amazoncategory = $this->amazon_xml_lib->readamazonInfoFromJsonFile( $amazonJsonFileName );
									if(isset($amazoncategory[$categoryID]))
									{
										$amazoncatagories = $amazoncategory[$categoryID]['value']['ProductType']['value'][$producttype];
										
										if(isset($amazoncatagories['value']))
					   					{
											if (count($amazoncatagories['value']) == count($amazoncatagories['value'], COUNT_RECURSIVE))
											{
												
											}
											else
											{
												$amazoncatagoriesvalues = $amazoncatagories['value'];
												foreach($amazoncatagoriesvalues as $amazoncatagoriesvalue)
												{
													if(isset($amazoncatagoriesvalue['value']))
													{
														if (count($amazoncatagoriesvalue['value']) == count($amazoncatagoriesvalue['value'], COUNT_RECURSIVE))
														{
															$name = $amazoncatagories['name'];
															$subname = $amazoncatagoriesvalue['name'];
															$nameid = $productID.'_producttype_'.$name.'_'.$subname;
															$valueForDropdown = $amazoncatagoriesvalue['value'];
															$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
														}
														else
														{
										
															$amazoncatagorieproducttypesubvalues = $amazoncatagoriesvalue['value'];
															foreach($amazoncatagorieproducttypesubvalues as $amazoncatagorieproducttypesubvalue)
															{
																if(isset($amazoncatagorieproducttypesubvalue['value']))
																{
																	if (count($amazoncatagorieproducttypesubvalue['value']) == count($amazoncatagorieproducttypesubvalue['value'], COUNT_RECURSIVE))
																	{
																		$name = $amazoncatagories['name'];
																		$subname = $amazoncatagoriesvalue['name'];
																		$subsubname = $amazoncatagorieproducttypesubvalue['name'];
																		$nameid = $productID.'_producttype_'.$name.'_'.$subname.'_'.$subsubname;
																		$subsubname = $subname.' - '.$amazoncatagorieproducttypesubvalue['name'];
																		$valueForDropdown = $amazoncatagorieproducttypesubvalue['value'];
																		$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
																	}
																	else 
																	{
																		$amazoncatagorieproducttypesubvaluechilrenvalue = array();
																		$amazoncatagorieproducttypesubvaluechilrens = $amazoncatagorieproducttypesubvalue['value'];
																		foreach($amazoncatagorieproducttypesubvaluechilrens as $amazoncatagorieproducttypesubvaluechilren)
																		{
																			$name = $amazoncatagories['name'];
																			$subname = $amazoncatagoriesvalue['name'];
																			$subsubname = $amazoncatagorieproducttypesubvalue['name'];
																			$subsubsubname = $amazoncatagorieproducttypesubvaluechilren['name'];
																			$nameid = $productID.'_producttype_'.$name.'_'.$subname.'_'.$subsubname.'_'.$subsubsubname;
																			$subsubsubname = $subsubname.' - '.$amazoncatagorieproducttypesubvaluechilren['name'];
																			if(isset($amazoncatagorieproducttypesubvaluechilren['value']))
																			{
																				$valueForDropdown = $amazoncatagorieproducttypesubvaluechilren['value'];
																				$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
																			}	
																			else
																			{
																				$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
																			}	
																		}	
																	}	
																}
																else
																{
																	$name = $amazoncatagories['name'];
																	$subname = $amazoncatagoriesvalue['name'];
																	$subsubname = $amazoncatagorieproducttypesubvalue['name'];
																	$nameid = $productID.'_producttype_'.$name.'_'.$subname.'_'.$subsubname;
																	$subsubname = $subname.' - '.$amazoncatagorieproducttypesubvalue['name'];
																	
																	if(isset($amazoncatagorieproducttypesubvalue['type']))
																	{
																		if($amazoncatagorieproducttypesubvalue['type'] == 'boolean')
																		{
																			$valueForDropdown = array('1'=>'True', '0'=>'False');
																			$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
																		}
																		elseif($amazoncatagorieproducttypesubvalue['type'] == 'positiveInteger' || $amazoncatagorieproducttypesubvalue['type'] == 'nonNegativeInteger' || $amazoncatagorieproducttypesubvalue['type'] == 'PositiveDimension' || $amazoncatagorieproducttypesubvalue['type'] =='Dimension')
																		{
																			$this->renderInputNumberHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
																		}
																		else
																		{
																			$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
																		}
																	}
																	else
																	{
																		$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
																	}
																}
															}
														}
													}
													else
													{
														$name = $amazoncatagories['name'];
														$subname = $amazoncatagoriesvalue['name'];
														$nameid = $productID.'_producttype_'.$name.'_'.$subname;
														
														if(isset($amazoncatagoriesvalue['type']))
														{
															if($amazoncatagoriesvalue['type'] == 'boolean')
															{
																$valueForDropdown = array('1'=>'True', '0'=>'False');
																$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
															}
															elseif($amazoncatagoriesvalue['type'] == 'positiveInteger' || $amazoncatagoriesvalue['type'] == 'nonNegativeInteger' || $amazoncatagoriesvalue['type'] == 'PositiveDimension' || $amazoncatagoriesvalue['type'] =='Dimension')
															{
																$this->renderInputNumberHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
															}
															else
															{
																$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
															}
														}
														else
														{
															$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
														}
													}	
												}
											}
										}
									}
								}	
							?>
							</div>
							<?php 
						}
						else 
						{
							if(isset($amazoncatagorieselement['value']))
							{
								if (count($amazoncatagorieselement['value']) == count($amazoncatagorieselement['value'], COUNT_RECURSIVE))
								{
									$name = $amazoncatagorieselement['name'];
									$nameid = $name;
									$valueForDropdown = $amazoncatagorieselement['value'];
									$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$name,$valueForDropdown,$categoryID,$productID,$marketPlace,"","");
								}	
								else
								{
									$amazoncatagorieselementmultiples = $amazoncatagorieselement['value'];
									
									foreach($amazoncatagorieselementmultiples as $amazoncatagorieselementmultiple)
									{
										if(isset($amazoncatagorieselementmultiple['value']))
										{
											if (count($amazoncatagorieselementmultiple['value']) == count($amazoncatagorieselementmultiple['value'], COUNT_RECURSIVE))
											{
												$name = $amazoncatagorieselement['name'];
												$subname = $amazoncatagorieselementmultiple['name'];
												$nameid = $name.'_'.$subname;
												$valueForDropdown = $amazoncatagorieselementmultiple['value'];
												$subname = $name.'-'.$subname;
												$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
											}
											else
											{
												$amazoncatagorieselementalues = $amazoncatagorieselementmultiple['value'];
												
												foreach($amazoncatagorieselementalues as $amazoncatagorieselementalue)
												{
													if(isset($amazoncatagorieselementalue['value']))
													{
														$name = $amazoncatagorieselement['name'];
														$subname = $amazoncatagorieselementmultiple['name'];
														$subsubname = $amazoncatagorieselementalue['name'];
														$nameid = $name.'_'.$subname.'_'.$subsubname;
														$subsubname = $subname.'-'.$subsubname;
														$valueForDropdown = $amazoncatagorieselementalue['value'];
														$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
													}
													else 
													{
														$name = $amazoncatagorieselement['name'];
														$subname = $amazoncatagorieselementmultiple['name'];
														$subsubname = $amazoncatagorieselementalue['name'];
														$nameid = $name.'_'.$subname.'_'.$subsubname;
														$subsubname = $subname.'-'.$subsubname;
														
														if(isset($amazoncatagorieselementalue['type']))
														{
															if($amazoncatagorieselementalue['type'] == 'boolean')
															{
																$valueForDropdown = array('1'=>'True', '0'=>'False');
																$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
															}
															elseif($amazoncatagorieselementalue['type'] == 'positiveInteger' || $amazoncatagorieselementalue['type'] == 'nonNegativeInteger' || $amazoncatagorieselementalue['type'] == 'PositiveDimension' || $amazoncatagorieselementalue['type'] =='Dimension')
															{
																$this->renderInputNumberHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
															}
															else
															{
																$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
															}
														}
														else
														{
															$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
														}
													}	
												}	
											}	
										}
										else
										{
											$name = $amazoncatagorieselement['name'];
											$subname = $amazoncatagorieselementmultiple['name'];
											$nameid = $name.'_'.$subname;
											$valueForDropdown = $amazoncatagorieselement['value'];
											$subname = $name.'-'.$subname;
											if(isset($amazoncatagorieselementmultiple['type']))
											{
												if($amazoncatagorieselementmultiple['type'] == 'boolean')
												{
													$valueForDropdown = array('1'=>'True', '0'=>'False');
													$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
												}
												elseif($amazoncatagorieselementmultiple['type'] == 'positiveInteger' || $amazoncatagorieselementmultiple['type'] == 'nonNegativeInteger' || $amazoncatagorieselementmultiple['type'] == 'PositiveDimension' || $amazoncatagorieselementmultiple['type'] =='Dimension')
												{
													$this->renderInputNumberHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
												}
												else
												{
													$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
												}
											}
											else
											{
												$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
											}
										}	
									}	
								}	
							}
							else 
							{
								$name = $amazoncatagorieselement['name'];
								$nameid = $name;
								
								if(isset($amazoncatagorieselement['type']))
								{
									if($amazoncatagorieselement['type'] == 'boolean')
									{
										$valueForDropdown = array('1'=>'True', '0'=>'False');
										$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$name,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
									}
									elseif($amazoncatagorieselement['type'] == 'positiveInteger' || $amazoncatagorieselement['type'] == 'nonNegativeInteger' || $amazoncatagorieselement['type'] == 'PositiveDimension' || $amazoncatagorieselement['type'] =='Dimension')
									{
										$this->renderInputNumberHTML($nameid,$name,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
									}
									else 
									{
										$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$name,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
									}	
								}
								else 
								{
									$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$name,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
								}
							}	
						}	
					}	
				?>
				</div>
	</div>
</div>
<?php 
		wp_die();
	}
	
	function fetch_amazon_category_product_type_profile()
	{
		global $global_ced_umb_amazon_Render_Attributes;
		
		$marketPlace = 'ced_umb_amazon_'.$this->marketplaceName;
		if(isset($_POST['profileid']))
		{
			$profileid = $_POST['profileid'];
		}
		
		global $wpdb,$global_ced_umb_amazon_Render_Attributes;
		$table_name = $wpdb->prefix.ced_umb_amazon_PREFIX.'profiles';
		$profile_data = array();
		if($profileid)
		{
			$query = "SELECT * FROM `$table_name` WHERE `id`=$profileid";
			$profile_data = $wpdb->get_results($query,'ARRAY_A');
			if(is_array($profile_data))
			{
				$profile_data = isset($profile_data[0]) ? $profile_data[0] : $profile_data;
				$profile_data = isset($profile_data['profile_data']) ? json_decode($profile_data['profile_data'],true) : array();
			}
		}
		
		$selectDropdownHTML= renderMetaSelectionDropdownOnProfilePageamazon();
		
		$categoryID = $_POST['catid'];
		$producttype = sanitize_text_field($_POST['catproducttype']);
		$productID = sanitize_text_field($_POST['productid']);
		$marketPlace = 'ced_umb_amazon_'.$this->marketplaceName;
		$amazonJsonFileName = 'amazon-category.json';
		$amazoncategory = $this->amazon_xml_lib->readamazonInfoFromJsonFile( $amazonJsonFileName );
		if(isset($amazoncategory[$categoryID]))
		{
			$amazoncatagories = $amazoncategory[$categoryID]['value']['ProductType']['value'][$producttype];
			if(isset($amazoncatagories['value']))
			{
				if (count($amazoncatagories['value']) == count($amazoncatagories['value'], COUNT_RECURSIVE))
				{
						
				}
				else
				{
					$amazoncatagoriesvalues = $amazoncatagories['value'];
					foreach($amazoncatagoriesvalues as $amazoncatagoriesvalue)
					{
						
						
						if(isset($amazoncatagoriesvalue['value']))
						{
							if (count($amazoncatagoriesvalue['value']) == count($amazoncatagoriesvalue['value'], COUNT_RECURSIVE))
							{
								$name = $amazoncatagories['name'];
								$subname = $amazoncatagoriesvalue['name'];
								$nameid = $productID.'_producttype_'.$name.'_'.$subname;
								
								$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
								$default = isset($fielddata['default']) ? $fielddata['default'] : null;
								$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
								$valueForDropdown = $amazoncatagoriesvalue['value'];
								
								echo "<div class='ced_amazon_attribute_wrapper'>";
								$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
								$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
								$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
								echo $updatedDropdownHTML;
								echo "</div>";
							}
							else
							{
								$amazoncatagorieproducttypesubvalues = $amazoncatagoriesvalue['value'];
								foreach($amazoncatagorieproducttypesubvalues as $amazoncatagorieproducttypesubvalue)
								{
									if(isset($amazoncatagorieproducttypesubvalue['value']))
									{
										if (count($amazoncatagorieproducttypesubvalue['value']) == count($amazoncatagorieproducttypesubvalue['value'], COUNT_RECURSIVE))
										{
											$name = $amazoncatagories['name'];
											$subname = $amazoncatagoriesvalue['name'];
											$subsubname = $amazoncatagorieproducttypesubvalue['name'];
											$nameid = $productID.'_producttype_'.$name.'_'.$subname.'_'.$subsubname;
											$subsubname = $subname.' - '.$amazoncatagorieproducttypesubvalue['name'];
											$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
											$default = isset($fielddata['default']) ? $fielddata['default'] : null;
											$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
											$valueForDropdown = $amazoncatagorieproducttypesubvalue['value'];
											echo "<div class='ced_amazon_attribute_wrapper'>";
											$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
											$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
											$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
											echo $updatedDropdownHTML;
											echo "</div>";
										}
										else
										{
											$amazoncatagorieproducttypesubvaluechilrenvalue = array();
											$amazoncatagorieproducttypesubvaluechilrens = $amazoncatagorieproducttypesubvalue['value'];
											foreach($amazoncatagorieproducttypesubvaluechilrens as $amazoncatagorieproducttypesubvaluechilren)
											{
												$name = $amazoncatagories['name'];
												$subname = $amazoncatagoriesvalue['name'];
												$subsubname = $amazoncatagorieproducttypesubvalue['name'];
												$subsubsubname = $amazoncatagorieproducttypesubvaluechilren['name'];
												$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
												$default = isset($fielddata['default']) ? $fielddata['default'] : null;
												$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
												$subsubsubname = $subsubname.' - '.$amazoncatagorieproducttypesubvaluechilren['name'];
												if(isset($amazoncatagorieproducttypesubvaluechilren['value']))
												{
													$valueForDropdown = $amazoncatagorieproducttypesubvaluechilren['value'];
													echo "<div class='ced_amazon_attribute_wrapper'>";
													$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
													$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
													$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
													echo $updatedDropdownHTML;
													echo "</div>";
												}
												else
												{
													echo "<div class='ced_amazon_attribute_wrapper'>";
													$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
													$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
													$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
													echo $updatedDropdownHTML;
													echo "</div>";
												}
											}
										}
									}
									else
									{
										$name = $amazoncatagories['name'];
										$subname = $amazoncatagoriesvalue['name'];
										$subsubname = $amazoncatagorieproducttypesubvalue['name'];
										$nameid = $productID.'_producttype_'.$name.'_'.$subname.'_'.$subsubname;

										$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
										$default = isset($fielddata['default']) ? $fielddata['default'] : null;
										$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
										$subsubname = $subname.' - '.$amazoncatagorieproducttypesubvalue['name'];
	
										if(isset($amazoncatagorieproducttypesubvalue['type']))
										{
											if($amazoncatagorieproducttypesubvalue['type'] == 'boolean')
											{
												echo "<div class='ced_amazon_attribute_wrapper'>";
													
												$valueForDropdown = array('1'=>'True', '0'=>'False');
												$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
												$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
												$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
												echo $updatedDropdownHTML;
												echo "</div>";
											}
											elseif($amazoncatagorieproducttypesubvalue['type'] == 'positiveInteger' || $amazoncatagorieproducttypesubvalue['type'] == 'nonNegativeInteger' || $amazoncatagorieproducttypesubvalue['type'] == 'PositiveDimension' || $amazoncatagorieproducttypesubvalue['type'] =='Dimension')
											{
												echo "<div class='ced_amazon_attribute_wrapper'>";
													
												$this->renderInputNumberHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
												$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
												$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
												echo $updatedDropdownHTML;
												echo "</div>";
											}
											else
											{
												echo "<div class='ced_amazon_attribute_wrapper'>";
													
												$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
												$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
												$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
												echo $updatedDropdownHTML;
												echo "</div>";
											}
										}
										else
										{
											echo "<div class='ced_amazon_attribute_wrapper'>";
												
											$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
											$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
											$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
											echo $updatedDropdownHTML;
											echo "</div>";
										}
									}
								}
							}
						}
						else
						{
							$name = $amazoncatagories['name'];
							$subname = $amazoncatagoriesvalue['name'];
							$nameid = $productID.'_producttype_'.$name.'_'.$subname;
							
							$fielddata = isset($profile_data[$categoryID.'_'.$nameid]) ? $profile_data[$categoryID.'_'.$nameid] : array();
							$default = isset($fielddata['default']) ? $fielddata['default'] : null;
							$metakey = isset($fielddata['metakey']) ? $fielddata['metakey'] : null;
							
							if(isset($amazoncatagoriesvalue['type']))
							{
								if($amazoncatagoriesvalue['type'] == 'boolean')
								{
									$valueForDropdown = array('1'=>'True', '0'=>'False');
									
									echo "<div class='ced_amazon_attribute_wrapper'>";
										
									$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
									$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
									$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
									echo $updatedDropdownHTML;
									echo "</div>";
								}
								elseif($amazoncatagoriesvalue['type'] == 'positiveInteger' || $amazoncatagoriesvalue['type'] == 'nonNegativeInteger' || $amazoncatagoriesvalue['type'] == 'PositiveDimension' || $amazoncatagoriesvalue['type'] =='Dimension')
								{
									echo "<div class='ced_amazon_attribute_wrapper'>";
									$this->renderInputNumberHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
									$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
									$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
									echo $updatedDropdownHTML;
									echo "</div>";
								}
								else
								{
									echo "<div class='ced_amazon_attribute_wrapper'>";
									$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
									$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
									$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
									echo $updatedDropdownHTML;
									echo "</div>";
								}
							}
							else
							{
								echo "<div class='ced_amazon_attribute_wrapper'>";
								$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>'profile','value'=>$default),"","");
								$updatedDropdownHTML = str_replace('{{*fieldID}}', $categoryID.'_'.$nameid, $selectDropdownHTML);
								$updatedDropdownHTML = str_replace('value="'.$metakey.'"', 'value="'.$metakey.'" selected="selected"', $updatedDropdownHTML);
								echo $updatedDropdownHTML;
								echo "</div>";
							}
						}
					}
				}
			}
		}
		wp_die();
	}
	
	/**
	 * Fetch Product type attribute
	 */
	function fetch_amazon_category_product_type()
	{
		global $global_ced_umb_amazon_Render_Attributes;
		$categoryID = $_POST['catid'];
		$producttype = sanitize_text_field($_POST['catproducttype']);
		$productID = sanitize_text_field($_POST['productid']);
		$marketPlace = 'ced_umb_amazon_'.$this->marketplaceName;
		$amazonJsonFileName = 'amazon-category.json';
		$amazoncategory = $this->amazon_xml_lib->readamazonInfoFromJsonFile( $amazonJsonFileName );
		if(isset($amazoncategory[$categoryID]))
		{
			$amazoncatagories = $amazoncategory[$categoryID]['value']['ProductType']['value'][$producttype];
			if(isset($amazoncatagories['value']))
			{
				if (count($amazoncatagories['value']) == count($amazoncatagories['value'], COUNT_RECURSIVE))
				{
						
				}
				else
				{
					$amazoncatagoriesvalues = $amazoncatagories['value'];
					foreach($amazoncatagoriesvalues as $amazoncatagoriesvalue)
					{
						if(isset($amazoncatagoriesvalue['value']))
						{
							if (count($amazoncatagoriesvalue['value']) == count($amazoncatagoriesvalue['value'], COUNT_RECURSIVE))
							{
								$name = $amazoncatagories['name'];
								$subname = $amazoncatagoriesvalue['name'];
								$nameid = $productID.'_producttype_'.$name.'_'.$subname;
								$valueForDropdown = $amazoncatagoriesvalue['value'];
								$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
							}
							else
							{
								$amazoncatagorieproducttypesubvalues = $amazoncatagoriesvalue['value'];
								foreach($amazoncatagorieproducttypesubvalues as $amazoncatagorieproducttypesubvalue)
								{
									if(isset($amazoncatagorieproducttypesubvalue['value']))
									{
										if (count($amazoncatagorieproducttypesubvalue['value']) == count($amazoncatagorieproducttypesubvalue['value'], COUNT_RECURSIVE))
										{
											$name = $amazoncatagories['name'];
											$subname = $amazoncatagoriesvalue['name'];
											$subsubname = $amazoncatagorieproducttypesubvalue['name'];
											$nameid = $productID.'_producttype_'.$name.'_'.$subname.'_'.$subsubname;
											$subsubname = $subname.' - '.$amazoncatagorieproducttypesubvalue['name'];
											$valueForDropdown = $amazoncatagorieproducttypesubvalue['value'];
											$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
										}
										else
										{
											$amazoncatagorieproducttypesubvaluechilrenvalue = array();
											$amazoncatagorieproducttypesubvaluechilrens = $amazoncatagorieproducttypesubvalue['value'];
											foreach($amazoncatagorieproducttypesubvaluechilrens as $amazoncatagorieproducttypesubvaluechilren)
											{
												$name = $amazoncatagories['name'];
												$subname = $amazoncatagoriesvalue['name'];
												$subsubname = $amazoncatagorieproducttypesubvalue['name'];
												$subsubsubname = $amazoncatagorieproducttypesubvaluechilren['name'];
												$nameid = $productID.'_producttype_'.$name.'_'.$subname.'_'.$subsubname.'_'.$subsubsubname;
												$subsubsubname = $subsubname.' - '.$amazoncatagorieproducttypesubvaluechilren['name'];
												if(isset($amazoncatagorieproducttypesubvaluechilren['value']))
												{
													$valueForDropdown = $amazoncatagorieproducttypesubvaluechilren['value'];
													$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
												}
												else
												{
													$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
												}
											}
										}
									}
									else
									{
										$name = $amazoncatagories['name'];
										$subname = $amazoncatagoriesvalue['name'];
										$subsubname = $amazoncatagorieproducttypesubvalue['name'];
										$nameid = $productID.'_producttype_'.$name.'_'.$subname.'_'.$subsubname;
										$subsubname = $subname.' - '.$amazoncatagorieproducttypesubvalue['name'];
	
										if(isset($amazoncatagorieproducttypesubvalue['type']))
										{
											if($amazoncatagorieproducttypesubvalue['type'] == 'boolean')
											{
												$valueForDropdown = array('1'=>'True', '0'=>'False');
												$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subsubname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
											}
											elseif($amazoncatagorieproducttypesubvalue['type'] == 'positiveInteger' || $amazoncatagorieproducttypesubvalue['type'] == 'nonNegativeInteger' || $amazoncatagorieproducttypesubvalue['type'] == 'PositiveDimension' || $amazoncatagorieproducttypesubvalue['type'] =='Dimension')
											{
												$this->renderInputNumberHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
											}
											else
											{
												$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
											}
										}
										else
										{
											$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subsubname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
										}
									}
								}
							}
						}
						else
						{
							$name = $amazoncatagories['name'];
							$subname = $amazoncatagoriesvalue['name'];
							$nameid = $productID.'_producttype_'.$name.'_'.$subname;
								
							if(isset($amazoncatagoriesvalue['type']))
							{
								if($amazoncatagoriesvalue['type'] == 'boolean')
								{
									$valueForDropdown = array('1'=>'True', '0'=>'False');
									$global_ced_umb_amazon_Render_Attributes->renderDropdownHTML($nameid,$subname,$valueForDropdown,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
								}
								elseif($amazoncatagoriesvalue['type'] == 'positiveInteger' || $amazoncatagoriesvalue['type'] == 'nonNegativeInteger' || $amazoncatagoriesvalue['type'] == 'PositiveDimension' || $amazoncatagoriesvalue['type'] =='Dimension')
								{
									$this->renderInputNumberHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
								}
								else
								{
									$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
								}
							}
							else
							{
								$global_ced_umb_amazon_Render_Attributes->renderInputTextHTML($nameid,$subname,$categoryID,$productID,$marketPlace,"",$indexToUse,array('case'=>"product"),"","");
							}
						}
					}
				}
			}
		}
		wp_die();
	}
	
	/**
	 * Process Meta data for Simple product
	 *
	 * @name ced_umb_amazon_required_fields_process_meta_simple
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	
	function ced_umb_amazon_required_fields_process_meta_simple( $post_id ) {
		$marketPlace = 'ced_umb_amazon_'.$this->marketplaceName;
		if(isset($_POST[$marketPlace])) {
			foreach ($_POST[$marketPlace] as $key => $field_name) {
				update_post_meta( $post_id, $field_name, sanitize_text_field( $_POST[$field_name][0] ) );
			}
		}
	}
	
	/**
	 * Render Marketplace Feed Details
	 *
	 * @name ced_umb_amazon_render_marketplace_feed_details
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	function ced_umb_amazon_render_marketplace_feed_details( $feedID, $marketplaceID)
	{
		if( $marketplaceID == $this->marketplaceID ) 
		{
			global $wpdb;
			$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
			$tableName = $prefix.'fileTracker';
			$sql = "SELECT * FROM `$tableName` WHERE `id`=$feedID";
			$detail = $wpdb->get_results($sql,'ARRAY_A');
			
			if(!is_array($detail) || !is_array($detail[0])) {
				_e('<h2>Sorry Details Not Found</h2>','ced-amazon-lister');
			}
			$detail = $detail[0];
			$response = $detail['response'];
			$response = json_decode($response,true);
	
			$feedId = $detail['product_ids'];//temp added
			if( $feedId ) {
				$response = $this->amazon_feed_manager->getFeedItemsStatus( $feedId );
			}
				
				
			if(isset($response['body']))
			{
				$finalxml = simplexml_load_string($response['body'], "SimpleXMLElement", LIBXML_NOCDATA);
				$finalstring = json_encode($finalxml);
				$finalresult = json_decode($finalstring,TRUE);
	
				_e('<h2 class="ced_umb_amazon_setting_header ced_umb_amazon_bottom_margin">Feed General Information</h2>','ced-amazon-lister');
	
	
				if(isset($finalresult['Error']['Message']))
				{
					echo '<table class="wp-list-table widefat fixed striped">';
					echo '<tbody>';
					echo '<tr>';
					echo '<th class="manage-column">'.$finalresult['Error']['Code'].'</th>';
					echo '<td class="manage-column">'.$finalresult['Error']['Message'].'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '</tbody>';
					echo '</table>';
				}
	
				if(isset($finalresult['Message']['ProcessingReport']['ProcessingSummary']))
				{
					$finalsummary = $finalresult['Message']['ProcessingReport']['ProcessingSummary'];
					echo '<table class="wp-list-table widefat fixed striped">';
					echo '<tbody>';
					echo '<tr>';
					echo '<th class="manage-column">Processed</th>';
					echo '<td class="manage-column">'.$finalsummary['MessagesProcessed'].'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<th class="manage-column">Successful</th>';
					echo '<td class="manage-column">'.$finalsummary['MessagesSuccessful'].'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<th class="manage-column">Error</th>';
					echo '<td class="manage-column">'.$finalsummary['MessagesWithError'].'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<th class="manage-column">Warning</th>';
					echo '<td class="manage-column">'.$finalsummary['MessagesWithWarning'].'</td>';
					echo '</tr>';
					echo '</tbody>';
					echo '</table>';
				}
	
				if(isset($finalresult['Message']['ProcessingReport']['Result']))
				{
					echo '<table class="wp-list-table widefat fixed striped">';
					echo '<tbody>';
					echo '<tr>';
					echo '<th class="manage-column">ResultCode</th>';
					echo '<th class="manage-column">ResultDescription</th>';
					echo '</tr>';
					$finalresults = isset($finalresult['Message']['ProcessingReport']['Result'][0])?$finalresult['Message']['ProcessingReport']['Result']:$finalresult['Message']['ProcessingReport'];
					foreach($finalresults as $key=>$result)
					{
						if($key == 'Result')
						{	
							echo '<tr>';
							echo '<th class="manage-column">'.$result['ResultCode'].'</th>';
							echo '<td class="manage-column">'.$result['ResultDescription'].'</td>';
							echo '</tr>';
						}
					}
					echo '</tbody>';
					echo '</table>';
				}
			}
		}
	}
	
	
	
	/**
	 * Cron for updating product price, inventory and image
	 */
	function ced_umb_amazon_cron_amazon_process()
	{
		$products = array();
		$args = array(
				'post_type'  => array('product'),
				'meta_query' => array(
						array(
								'key'   => '_umb_amazon_category',
								'value' => "",
								'compare' => '!=',
						),
				),
		);
		
		$simpleproducts = get_posts( $args );
		
		if(isset($simpleproducts) && !empty($simpleproducts))
		{
			foreach($simpleproducts as $simpleproduct)
			{
				$productid = $simpleproduct->ID;
				$products[$productid] = $productid;
			}	
		}	
		
		
		$isWriteXML = false;
		
		$this->makeInventoryXMLFileToSendOnAmazon($products,$isWriteXML);	//create inventory xml file
		
		$xmlFileName = "inventory.xml";
		$directorypath = plugin_dir_path(__FILE__);
		$xsdfile = "$directorypath/upload/xsds/amzn-envelope.xsd";
		if($this->validateXML( $xsdfile,$xmlFileName))					//validate inventory xml before uploading
		{
			if(!class_exists("AmazonFeed"))
			{
				require($directorypath.'/lib/amazon/includes/classes.php');
			}
			
			$this->amazon_lib = new AmazonFeed();
			$XMLfilePath = ABSPATH.'wp-content/uploads/umb/amazon/';
			$XMLfilePath .= $xmlFileName;
			$xmlstring = file_get_contents($XMLfilePath);
			$this->amazon_lib->setFeedType("_POST_INVENTORY_AVAILABILITY_DATA_");
			$this->amazon_lib->setFeedContent($xmlstring);
			$this->amazon_lib->submitFeed();									//submit inventory feed
			$inventoryuploadreponse = $this->amazon_lib->getResponse();
			
			if(isset($inventoryuploadreponse['FeedSubmissionId']))
			{
				$feedId = $inventoryuploadreponse['FeedSubmissionId'];
				$this->insertFeedInfoToDatabase($feedId);				//save inventory feed
		
				$this->makePriceXMLFileToSendOnAmazon($products,$isWriteXML);	//create Price xml file
				$xmlFileName = "price.xml";
				$xsdfile = "$directorypath/upload/xsds/amzn-envelope.xsd";
					
				if($this->validateXML( $xsdfile,$xmlFileName))					//validate Price xml before uploading
				{
					$XMLfilePath = ABSPATH.'wp-content/uploads/umb/amazon/';
					$XMLfilePath .= $xmlFileName;
					$xmlstring = file_get_contents($XMLfilePath);
					$this->amazon_lib->setFeedType("_POST_PRODUCT_PRICING_DATA_");
					$this->amazon_lib->setFeedContent($xmlstring);
					$this->amazon_lib->submitFeed();									//submit Price feed
					$priceuploadreponse = $this->amazon_lib->getResponse();
					if(isset($priceuploadreponse['FeedSubmissionId']))
					{
						$feedId = $priceuploadreponse['FeedSubmissionId'];
						$this->insertFeedInfoToDatabase($feedId);
			 			
						$this->makeImageXMLFileToSendOnAmazon($products,$isWriteXML);
						$xmlFileName = "image.xml";
			 			
						$xsdfile = "$directorypath/upload/xsds/amzn-envelope.xsd";
						if($this->validateXML( $xsdfile,$xmlFileName))					//validate Image xml before uploading
						{
							$XMLfilePath = ABSPATH.'wp-content/uploads/umb/amazon/';
							$XMLfilePath .= $xmlFileName;
							$xmlstring = file_get_contents($XMLfilePath);
							$this->amazon_lib->setFeedType("_POST_PRODUCT_IMAGE_DATA_");
							$this->amazon_lib->setFeedContent($xmlstring);
							$this->amazon_lib->submitFeed();									//submit Image feed
							$imageuploadreponse = $this->amazon_lib->getResponse();
							if(isset($imageuploadreponse['FeedSubmissionId']))
							{
								$feedId = $imageuploadreponse['FeedSubmissionId'];
								$this->insertFeedInfoToDatabase($feedId);
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * This function to fetch order from amazon seller panel having status "CREATED"
	 *
	 * @name fetchOrders
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	
	function fetchOrders($status='Unshipped', $cron=false)
	{
		try
		{
			$directorypath = plugin_dir_path(__FILE__);
			if(!class_exists("AmazonOrderList"))
			{
				require($directorypath.'/lib/amazon/includes/classes.php');
			}
			$amz = new AmazonOrderList(); 
			$amz->setLimits('Created', "31-12-2016");
			$amz->setOrderStatusFilter( array("Unshipped", "PartiallyShipped","Shipped") );
			 //no shipped or pending orders
			$amz->setUseToken();
			$orders = $amz->fetchOrders();
			$orderlists = $amz->getList();
			
			
			if(isset($orderlists) && !empty($orderlists))
			{
				foreach($orderlists as $orderlist)
				{
					$amazon_order_detail = $orderlist->getData();
					
					$amazonorderid = $amazon_order_detail['AmazonOrderId'];
					$ShipToFirstName = isset($amazon_order_detail['ShippingAddress']['Name'])?$amazon_order_detail['ShippingAddress']['Name']:"";
					$ShipToAddress1 = isset($amazon_order_detail['ShippingAddress']['AddressLine1'])?$amazon_order_detail['ShippingAddress']['AddressLine1']:"";
					$ShipToAddress2 = isset($amazon_order_detail['ShippingAddress']['AddressLine2'])?$amazon_order_detail['ShippingAddress']['AddressLine2']:"";
					$ShipToAddress3 = isset($amazon_order_detail['ShippingAddress']['AddressLine3'])?$amazon_order_detail['ShippingAddress']['AddressLine3']:"";
					$ShipToCityName = isset($amazon_order_detail['ShippingAddress']['City'])?$amazon_order_detail['ShippingAddress']['City']:"";
					$ShipToCountyName = isset($amazon_order_detail['ShippingAddress']['County'])?$amazon_order_detail['ShippingAddress']['County']:"";
					$ShipToDistrictName = isset($amazon_order_detail['ShippingAddress']['District'])?$amazon_order_detail['ShippingAddress']['District']:"";
					$ShipToStateOrRegionName = isset($amazon_order_detail['ShippingAddress']['StateOrRegion'])?$amazon_order_detail['ShippingAddress']['StateOrRegion']:"";
					$ShipToZipCode = isset($amazon_order_detail['ShippingAddress']['PostalCode'])?$amazon_order_detail['ShippingAddress']['PostalCode']:"";
					$ShipToCountry = isset($amazon_order_detail['ShippingAddress']['CountryCode'])?$amazon_order_detail['ShippingAddress']['CountryCode']:"";
					$ShipToPhone = isset($amazon_order_detail['ShippingAddress']['Phone'])?$amazon_order_detail['ShippingAddress']['Phone']:"";
					
					$ShippingAddress = array(
							'first_name' => $ShipToFirstName,
							'address_1' => $ShipToAddress1,
							'address_2' => $ShipToAddress2,
							'city' => $ShipToCityName,
							'county' => $ShipToCountyName,
							'district' => $ShipToDistrictName,
							'state' => $ShipToStateOrRegionName,
							'postcode'	=> $ShipToZipCode,
							'country' => $ShipToCountry,
							'phoneno'	=> $ShipToPhone,
					);

					$buyeremail = $amazon_order_detail['BuyerEmail'];
					$buyername = $amazon_order_detail['BuyerName'];

					$BillingAddress = array(
							'first_name' => $buyername,
							'email'	=> $buyeremail,
					);
					$paymentmethod = isset($amazon_order_detail['PaymentMethod'])?$amazon_order_detail['PaymentMethod']:"";
					$amazon_order_array = array(
						'shipping' => $ShippingAddress,
						'billing'  => $BillingAddress,
						'sellerid'=> $amazon_order_detail['SellerOrderId'],
						'purchasedate'=> $amazon_order_detail['PurchaseDate'],
						'lastupdate'=> $amazon_order_detail['LastUpdateDate'],
						'orderstatus'=> $amazon_order_detail['OrderStatus'],
						'shipservicelevel'=>$amazon_order_detail['ShipServiceLevel'],
						'currency'=> $amazon_order_detail['OrderTotal']['CurrencyCode'],
						'total'=> $amazon_order_detail['OrderTotal']['Amount'],
						'paymethod'=>$paymentmethod,
						
					);

					
					
					$OrderNumber = isset($amazon_order_detail['AmazonOrderId']) ? $amazon_order_detail['AmazonOrderId'] : '';
					
					
					if(!class_exists('ced_umb_amazon_order_manager')){
						require_once ced_umb_amazon_DIRPATH.'admin/helper/class-order-manager.php';
					}
					
					$OrderInstance = ced_umb_amazon_order_manager::get_instance();
						
					
					$amazonOrderMeta = array(
							'amazon_order_id' => $OrderNumber,
							'order_detail' => $amazon_order_array,
					);

					$OrderInstance->save_order_listing($amazonOrderMeta );							
				}
				$message = __('Orders Fetched Successfully.','ced-amazon-lister');
				$classes = "notice notice-success";
				$success = array('message'=>$message,'classes'=>$classes);
				$notices[] = $success;
				return $notices;
			}
			
		}
		catch(Exception $ex)
		{
			echo __('There was a problem with the Amazon library. Error:','ced-amazon-lister') .$ex->getMessage();
		}
	}	
	
	/**
	 * Render Input number html
	 * @param unknown $attribute_id
	 * @param unknown $attribute_name
	 * @param unknown $categoryID
	 * @param unknown $productID
	 * @param unknown $marketPlace
	 * @param string $attribute_description
	 * @param unknown $indexToUse
	 * @param unknown $additionalInfo
	 * @param string $conditionally_required
	 * @param string $conditionally_required_text
	 */
	function renderInputNumberHTML($attribute_id,$attribute_name,$categoryID,$productID,$marketPlace,$attribute_description=null,$indexToUse,$additionalInfo=array('case'=>"product"),$conditionally_required=false,$conditionally_required_text='' ) 
	{
		global $post,$product,$loop;
		$fieldName = $categoryID.'_'.$attribute_id;
		if($additionalInfo['case'] == "product") {
			$previousValue = get_post_meta ( $productID, $fieldName, true );
		}
		else{
			$previousValue = $additionalInfo['value'];
		}
	
		?>
<p class="form-field _umb_brand_field ">
	<input type="hidden" name="<?php echo $marketPlace.'[]'; ?>"
		value="<?php echo $fieldName; ?>" /> <label for=""><?php echo $attribute_name; ?>
			</label> <input class="short" style=""
		name="<?php echo $fieldName.'['.$indexToUse.']'; ?>" id=""
		value="<?php echo $previousValue; ?>" type="number" /> 
			<?php
			if(!is_null($attribute_description) && $attribute_description != '') {
				echo wc_help_tip( __( $attribute_description, 'ced-amazon-lister' ) );
			}
			if($conditionally_required) {
				echo wc_help_tip( __( $conditionally_required_text, 'ced-amazon-lister' ) );
			}
			?>
		</p>
<?php
	}
	
	/**
	 * amazon required fields.
	 *
	 * @name add_blank_element_on_array
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	public function add_blank_element_on_array($descriptiondatavalues=array())
	{
		$returned_array[""] = "-- SELECT --";
		foreach($descriptiondatavalues as $key=>$value)
		{
			$returned_array[$key] = $value;
		}
		return $returned_array;	
	}	
	
	/**
	 * validate the function.
	 *
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	public function validate($proId)
	{ 
		$woo_ver = WC()->version;
		$directorypath = plugin_dir_path(__FILE__);
		$this->amazon_xml_lib->fetchAssignedProfileDataOfProduct($proId);
		$assignedamazonCategory = $this->amazon_xml_lib->fetchMetaValueOfProduct( $proId, '_umb_amazon_category' );
		if(!isset($assignedamazonCategory) || empty($assignedamazonCategory) || $assignedamazonCategory == "" || $assignedamazonCategory == "null")
		{
			$statusArray['isReady'] = false;
			$statusArray['missingData'] = array(__('Amazon Category Not Assigned.','ced-amazon-lister'));
		}
		else
		{
			$proid = array();
			
			$product = new WC_Product($proId);
			if($woo_ver < "3.0.0" && $woo_ver < "2.7.0")
			{
				$productid = $product->get_parent();
			}
			else
			{
				$productid = $product->get_parent_id();
			}
			if($productid > 0)
			{
				$proid[] = $productid;
			}	
			else
			{
				$proid[] = $proId;
			}	
			$this->amazon_product_update->errorTrackArray = array();
			$statusArray['isReady'] = true;
			$this->makeProductXMLFileToSendOnAmazon($proid);
			$xmlFileName = "product.xml";
			$xsdfile = "$directorypath/upload/xsds/amzn-envelope.xsd";
			if($this->validateXML( $xsdfile,$xmlFileName, false))					//validate product upload xml before uploading
			{
				$statusArray['isReady'] = true;
			}	
			else
			{
				$statusArray['isReady'] = false;
				$error = $this->validateXML( $xsdfile,$xmlFileName, false, true);
				$statusArray['missingData'] = array($error);
			}
		}
		return $statusArray;
	 }
	 
	 /**
	  * Upload Product on amazon
	  * @param unknown $proIds
	  * @param string $isWriteXML
	  * @return Ambigous <multitype:, boolean, string>
	  */
	 public function upload($proIds=array(), $isWriteXML=true)
	 {
	 	$directorypath = plugin_dir_path(__FILE__);
	 	$this->makeProductXMLFileToSendOnAmazon($proIds,$isWriteXML, "upload-product.xml");   //Create xml file for product upload
		
	 	$xmlFileName = "upload-product.xml";
	 	$xsdfile = "$directorypath/upload/xsds/amzn-envelope.xsd";
	 	if($this->validateXML( $xsdfile, $xmlFileName, false))					//validate product upload xml before uploading
	 	{
	 		$XMLfilePath = ABSPATH.'wp-content/uploads/umb/amazon/';
	 		$XMLfilePath .= $xmlFileName;
	 		$xmlstring = file_get_contents($XMLfilePath);
	 		$directorypath = plugin_dir_path(__FILE__);
	 		
	 		if(!class_exists("AmazonFeed"))
	 		{
	 			require($directorypath.'/lib/amazon/includes/classes.php');
	 		}	
	 		$this->amazon_lib = new AmazonFeed();
	 		$this->amazon_lib->setFeedType("_POST_PRODUCT_DATA_");
	 		$this->amazon_lib->setFeedContent($xmlstring);
	 		$this->amazon_lib->submitFeed(); 				//Submit the upload request
	 									
	 		$productuploadreponse = $this->amazon_lib->getResponse();
	 		if(isset($productuploadreponse['FeedSubmissionId']))
	 		{
	 			$feedId = $productuploadreponse['FeedSubmissionId'];
	 			$this->insertFeedInfoToDatabase($feedId);				//save product feed
	 			$notice['message'] = __('Your product upload to amazon is successfully send.','ced-amazon-lister');
	 			$notice['classes'] = 'notice notice-success is-dismissable';
	 			return json_encode($notice);
	 		}	
	 	}
	 	else 
	 	{
	 		$notice['message'] = $this->validateXML( $xsdfile, $xmlFileName, false);
	 		$notice['message'] = __('Product needs to be ready.','ced-amazon-lister');
	 		$notice['classes'] = 'notice notice-error is-dismissable';
	 		return json_encode($notice);
	 	}		
	 }	
	 
	 /**
	  * Create Price File
	  *
	  * @name makeImageXMLFileToSendOnAmazon
	  * @author CedCommerce <plugins@cedcommerce.com>
	  * @since 1.0.0
	  */
	 function makeImageXMLFileToSendOnAmazon( $proIds,$isWriteXML )
	 {
	 	$directorypath = plugin_dir_path(__FILE__);
	 	$amazonxmlsubarray = array();
	 	$amazonxmlsubarray['@attributes'] = array(
	 			'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
	 			'xsi:noNamespaceSchemaLocation' => "amzn-envelope.xsd"
	 	);
	 
	 	$amazonxmlsubarray['Header']['DocumentVersion'] = "1.01";
	 	$amazonxmlsubarray['Header']['MerchantIdentifier'] = "M_SELLER_XXXXXX";
	 	$amazonxmlsubarray['MessageType'] = "ProductImage";
	 	
	 	$i = 1;
	 	foreach($proIds as $product_id)
	 	{
	 		$assignedamazonCategory = $this->amazon_xml_lib->fetchMetaValueOfProduct( $product_id, '_umb_amazon_category' );
	 		if(isset($assignedamazonCategory) && !empty($assignedamazonCategory))
	 		{
	 			$product = wc_get_product( $product_id );
	 			$productType = $product->get_type();
	 			$post_thumbnail_id = get_post_thumbnail_id( $product_id );
 				$image = wp_get_attachment_image_url($post_thumbnail_id, "thumbnail", false);
 				$amazonxmlarray = array();
 				$sku = $product->get_sku();
 				if(isset($image) && !empty($image) && isset($sku) && !empty($sku))
 				{
 					$amazonxmlarray['MessageID'] = $i;
 					$amazonxmlarray['OperationType'] = "Update";
 					$amazonxmlarray['ProductImage']['SKU'] = $product->get_sku();
 					$amazonxmlarray['ProductImage']['ImageType'] = "Main";
 					$amazonxmlarray['ProductImage']['ImageLocation'] = $image;
 					$amazonxmlsubarray['Message'][] = $amazonxmlarray;
 					$i = $i+1;
 				}
	 			
	 			$attachment_ids = $product->get_gallery_attachment_ids();
	 			
	 			$j=1;
	 			foreach( $attachment_ids as $attachment_id )
	 			{
	 				$alternateimage = wp_get_attachment_url( $attachment_id );
	 				$amazonxmlarray['MessageID'] = $i;
	 				$amazonxmlarray['OperationType'] = "Update";
	 				$amazonxmlarray['ProductImage']['SKU'] = $product->get_sku();
	 				$amazonxmlarray['ProductImage']['ImageType'] = "PT$j";
	 				$amazonxmlarray['ProductImage']['ImageLocation'] = $alternateimage;
	 				$amazonxmlsubarray['Message'][] = $amazonxmlarray;
	 				$i = $i+1;
	 				$j = $j+1;
	 			}
	 		}
	 	}
	 	
	 	require_once 'lib/array2xml.php';
	 	$xml = Array2XML::createXML('AmazonEnvelope', $amazonxmlsubarray);
	 	$xmlFileName = "image.xml";
	 	$xmlString = $xml->saveXML();
	 	$this->writeXMLStringToFile( $xmlString, $xmlFileName );
	 }
	 
	 /**
	  * Create Price File
	  *
	  * @name insertFeedInfoToDatabase
	  * @author CedCommerce <plugins@cedcommerce.com>
	  * @since 1.0.0
	  */
	 function makePriceXMLFileToSendOnAmazon( $proIds,$isWriteXML )
	 {
	 	$directorypath = plugin_dir_path(__FILE__);
	 	$amazonxmlsubarray = array();
	 	$amazonxmlsubarray['@attributes'] = array(
	 			'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
	 			'xsi:noNamespaceSchemaLocation' => "amzn-envelope.xsd"
	 	);
	 	 
	 	$amazonxmlsubarray['Header']['DocumentVersion'] = "1.01";
	 	$amazonxmlsubarray['Header']['MerchantIdentifier'] = "M_SELLER_XXXXXX";
	 	$amazonxmlsubarray['MessageType'] = "Price";
	 	$i=1;
	 	foreach($proIds as $product_id)
	 	{
	 		$assignedamazonCategory = $this->amazon_xml_lib->fetchMetaValueOfProduct( $product_id, '_umb_amazon_category' );
	 		if(isset($assignedamazonCategory) && !empty($assignedamazonCategory))
	 		{
	 			$product = wc_get_product( $product_id );
	 			$productType = $product->get_type();
	 			if($productType == 'simple')
	 			{
	 				$amazonxmlarray = array();
	 				$qty = $product->get_stock_quantity();
	 				$sku = $product->get_sku();
	 				
	 				if(isset($qty) && !empty($qty) && isset($sku) && !empty($sku))
	 				{
	 					$amazonxmlarray['MessageID'] = $i;
	 					$amazonxmlarray['OperationType'] = "Update";
	 					$amazonxmlarray['Price']['SKU'] = $product->get_sku();
	 					$amazonxmlarray['Price']['StandardPrice']['@attributes']['currency'] = "USD";
	 					$amazonxmlarray['Price']['StandardPrice']['@value'] = $product->get_price();
	 					$amazonxmlsubarray['Message'][] = $amazonxmlarray;
	 					$i++;
	 				}
	 			}
	 			else
	 			{
	 			
	 			}
	 		}
	 	}
	 	 
	 	require_once 'lib/array2xml.php';
	 	$xml = Array2XML::createXML('AmazonEnvelope', $amazonxmlsubarray);
	 	$xmlFileName = "price.xml";
	 	$xmlString = $xml->saveXML();
	 	$this->writeXMLStringToFile( $xmlString, $xmlFileName );
	 }
	 
	 /**
	  * Create Inventory File
	  *
	  * @name insertFeedInfoToDatabase
	  * @author CedCommerce <plugins@cedcommerce.com>
	  * @since 1.0.0
	  */
	 function makeInventoryXMLFileToSendOnAmazon( $proIds,$isWriteXML )
	 {
	 	$directorypath = plugin_dir_path(__FILE__);
	 	$amazonxmlsubarray = array();
	 	$amazonxmlsubarray['@attributes'] = array(
	 			'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
	 			'xsi:noNamespaceSchemaLocation' => "amzn-envelope.xsd"
	 	);
	 	
	 	$amazonxmlsubarray['Header']['DocumentVersion'] = "1.01";
	 	$amazonxmlsubarray['Header']['MerchantIdentifier'] = "M_SELLER_XXXXXX";
	 	$amazonxmlsubarray['MessageType'] = "Inventory";
	 	$i=1;
	 	foreach($proIds as $product_id)
	 	{
	 		$assignedamazonCategory = $this->amazon_xml_lib->fetchMetaValueOfProduct( $product_id, '_umb_amazon_category' );
	 		if(isset($assignedamazonCategory) && !empty($assignedamazonCategory))
	 		{
	 			$product = wc_get_product( $product_id );
				$productType = $product->get_type();
				if($productType == 'simple')
				{
					$amazonxmlarray = array();
					$qty = $product->get_stock_quantity();
					$sku = $product->get_sku();
					if(isset($qty) && !empty($qty) && isset($sku) && !empty($sku))
					{
						$amazonxmlarray['MessageID'] = $i;
						$amazonxmlarray['OperationType'] = "Update";
						$amazonxmlarray['Inventory']['SKU'] = $product->get_sku();
						$amazonxmlarray['Inventory']['Quantity'] =  $product->get_stock_quantity();
						$amazonxmlarray['Inventory']['FulfillmentLatency'] = $this->amazon_xml_lib->fetchMetaValueOfProduct( $product_id, '_umb_amazon_FulfillmentLatency' );
						$amazonxmlsubarray['Message'][] = $amazonxmlarray;
						$i++;
					}
				}
				else 
				{

				}	
	 		}	
	 	}	
	 	require_once 'lib/array2xml.php';
	 	$xml = Array2XML::createXML('AmazonEnvelope', $amazonxmlsubarray);
	 	$xmlFileName = "inventory.xml";
	 	$xmlString = $xml->saveXML();
	 	$this->writeXMLStringToFile( $xmlString, $xmlFileName );
	 }	
	 
	 /**
	  * SAVE FEEDID
	  *
	  * @name insertFeedInfoToDatabase
	  * @author CedCommerce <plugins@cedcommerce.com>
	  * @since 1.0.0
	  */
	 function insertFeedInfoToDatabase( $feedId ) 
	 {
	 	$response = $this->amazon_feed_manager->getFeedItemsStatus( $feedId );
	 	$response = json_encode($response['body']);
	 	$marketPlace = 'amazon';
	 	$uploadingProIds = $feedId;//temp
	 	$timeOfRequest = $feedId;
	 	$name = "Feed Id : ".$timeOfRequest;
	 	global $wpdb;
	 	$prefix = $wpdb->prefix . ced_umb_amazon_PREFIX;
	 	$tableName = $prefix.'fileTracker';
	 	$query = "INSERT INTO `$tableName`( `name`, `product_ids`, `framework`, `response` ) VALUES ('".$name."','".$uploadingProIds."','".$marketPlace."','".$response."');";
	 	$wpdb->query($query);
	 }
	 
	 /**
	  * Make product xml file send on amaozn
	  * @param unknown $proIds
	  * @param string $isWriteXML
	  * @param string $xmlFileName
	  */
	 function makeProductXMLFileToSendOnAmazon($proIds=array(), $isWriteXML=false, $xmlFileName = "product.xml") 
	 {
	 	$mainamazonxmlarray = array();
		$mainamazonxmlarray['@attributes'] = array(
						'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
						'xsi:noNamespaceSchemaLocation' => "amzn-envelope.xsd"
				);
		$mainamazonxmlarray['Header']['DocumentVersion'] = "2.1";
		$mainamazonxmlarray['Header']['MerchantIdentifier'] = "M_SELLER_354577";
		$mainamazonxmlarray['MessageType'] = "Product";
		$mainamazonxmlarray['PurgeAndReplace'] = "false";
		$isPrimaryVariant = false;
		
		$k = 1;
		
		foreach ($proIds as $key => $product_id) 
		{
			$amazonxmlarrayresponse = $this->amazon_xml_lib->makeArrayFor_MPITEM($product_id,$isPrimaryVariant, $k);
			if(isset($amazonxmlarrayresponse) && !empty($amazonxmlarrayresponse))
			{
				if(isset($amazonxmlarrayresponse[0]))
				{
					foreach($amazonxmlarrayresponse as $amazonxmlarrayrespons)
					{
						$amazonxmlarray[] = $amazonxmlarrayrespons;
						$k = $k + 1;
					}	
				}
				else
				{
					$amazonxmlarray[] = $amazonxmlarrayresponse;
				}
			}	
			$k = $k + 1;
		}
		
		if(isset($amazonxmlarray) && sizeof($amazonxmlarray) == 1)
		{
			$amazonxmlarray = $amazonxmlarray[0];
		}	
		if(isset($amazonxmlarray)){
			$mainamazonxmlarray['Message'] = $amazonxmlarray;
		}
		
		
		if(!class_exists("Array2XML"))
		{
			require_once 'lib/array2xml.php';
		}
		
		$xml = Array2XML::createXML('AmazonEnvelope', $mainamazonxmlarray);
		$xmlString = $xml->saveXML();
		
		$this->writeXMLStringToFile( $xmlString, $xmlFileName );
	}
	
	/**
	 * This function writes xml string to destination file.
	 * @name writeXMLStringToFile()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function writeXMLStringToFile( $xmlString, $fileName ) 
	{
		$XMLfilePath = ABSPATH.'wp-content/uploads/umb/';
		if(!is_dir($XMLfilePath))
		{
			if(!mkdir($XMLfilePath,0755))
			{
				return false;
			}
		}
		$XMLfilePath = $XMLfilePath."amazon/";
		if(!is_dir($XMLfilePath))
		{
			if(!mkdir($XMLfilePath,0755))
			{
				return false;
			}
		}
		
		if(!is_writable($XMLfilePath))
		{
			return false;
		}
		$XMLfilePath .= $fileName;
		$XMLfile = fopen($XMLfilePath, 'w');
		fwrite($XMLfile, $xmlString);
		fclose($XMLfile);
	}
	
	/**
	 * validate XML against xsd before sending to Amazon
	 *
	 * @name ced_umb_amazon_required_fields_process_meta_simple
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @since 1.0.0
	 */
	function validateXML( $xsdfile,$xmlFileName, $showerror = true, $returnerror = false) 
	{
		$return = true;
		$XMLfilePath = ABSPATH.'wp-content/uploads/umb/';
		$XMLfilePath = $XMLfilePath."amazon/";
		$XMLfilePath .= $xmlFileName;
	
		libxml_use_internal_errors(true);
		$feed = new DOMDocument();
		$feed->preserveWhitespace = false;
		$result = $feed->load($XMLfilePath);
		if($result === TRUE)
		{
			if(@($feed->schemaValidate($xsdfile)))
			{
				global $cedumbamazonhelper;
	
				if($showerror)
				{
					echo "Valid";
				}	
				$log_detail = "\nmessage: Product XML ERRORS \n";
				$log_detail .= "No Errors :: Valid XML"."\n******************************************************************\n\n\n\n\n";
				$cedumbamazonhelper->umb_write_logs("amazon-product-xml.log",$log_detail);
			}
			else
			{
				$return = false;
				$errors = libxml_get_errors();
				$errorList = "";
				foreach($errors as $error) {
					$errorList .= "---\n";
					$errorList .= 	$error->message."\n";
					$errorList .= 	"<br/>";
				}
				if($showerror)
				{
					echo $errorList;
				}
				if($returnerror)
				{
					return $errorList;	
				}	
				global $cedumbamazonhelper;
				$log_detail = "\nmessage: Product XML ERRORS \n";
				$log_detail .= $errorList."\n******************************************************************\n\n\n\n\n";
				$cedumbamazonhelper->umb_write_logs("amazon-product-xml.log",$log_detail);
			}
		}
		else {
			$return = false;
			$errors = "! Document is not valid:\n";
			if($showerror)
			{	
				echo $errors;
			}	
			global $cedumbamazonhelper;
			$log_detail = "\nmessage: Product XML ERRORS \n";
			$log_detail .= $errors."\n******************************************************************\n\n\n\n\n";
			$cedumbamazonhelper->umb_write_logs("amazon-product-xml.log",$log_detail);
		}
	
		return $return;
	}
}
endif;