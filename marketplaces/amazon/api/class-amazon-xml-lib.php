<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Manage xml related functions to use in amazon.
 *
 * @class    ced_umb_amazon_amazon_XML_Lib
 * @version  1.0.0
 * @category Class
 * @author   CedCommerce
 */

class ced_umb_amazon_Amazon_XML_Lib {
	
	public $isProfileAssignedToProduct = false;

	/**
	 * This function reads json data from json files and return data in array form.
	 * @name readamazonInfoFromJsonFile()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function readamazonInfoFromJsonFile( $amazonJsonFileName ) {
		$amazonJsonFilePath = 'json/'.$amazonJsonFileName;
		$amazonJsonFilePath = ced_umb_amazon_DIRPATH.'marketplaces/amazon/partials/'.$amazonJsonFilePath;
		ob_start();
		readfile($amazonJsonFilePath);
		$json_data = ob_get_clean();
		$json_data_to_array = json_decode($json_data, TRUE);
		return $json_data_to_array;
	}
	
	/**
	 * This function fetches meta value of a product in accordance with profile assigned and meta value available.
	 * @name fetchMetaValueOfProduct()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	
	function fetchMetaValueOfProduct( $product_id, $metaKey ) {
	
		if($this->isProfileAssignedToProduct) {
			
			$_product = wc_get_product($product_id);
			if( $_product->get_type() == "variation" ) {
				$parentId = $_product->parent->id;
			}
			else {
				$parentId = "0";
			}
			
			if (strpos($metaKey, '_producttype_') !== false)
			{
				$producttypemetakey = $metaKey;
				$metakeystring = "";
				$metakeyarray = explode("_", $producttypemetakey);
				$metakeyarray[1] = 0;
				
				foreach($metakeyarray as $metakeyarr)
				{
					$metakeystring .= $metakeyarr."_";
				}
			
				$metaKey = rtrim($metakeystring,'_');
			
			}
			
			if(!empty($this->profile_data) && isset($this->profile_data[$metaKey])) {
				$tempProfileData = $profileData = $this->profile_data[$metaKey];
	
				if( isset($tempProfileData['default']) && !empty($tempProfileData['default']) && $tempProfileData['default'] != "" && !is_null($tempProfileData['default']) ) {
					$value = $tempProfileData['default'];
				}
				else if( isset($tempProfileData['metakey']) && !empty($tempProfileData['metakey']) && $tempProfileData['metakey'] != "" && !is_null($tempProfileData['metakey']) ) {
						
					//if woo attribute is selected
					if (strpos($tempProfileData['metakey'], 'umb_pattr_') !== false) {
	
						$wooAttribute = explode('umb_pattr_', $tempProfileData['metakey']);
						$wooAttribute = end($wooAttribute);
							

						$wooAttributeValue = $_product->get_attribute( 'pa_'.$wooAttribute );
						$product_terms = get_the_terms($product_id, 'pa_'.$wooAttribute);
						if(is_array($product_terms) && !empty($product_terms)) {
							foreach ($product_terms as $tempkey => $tempvalue) {
								if($tempvalue->slug == $wooAttributeValue ) {
									$wooAttributeValue = $tempvalue->name;
									break;
								}
							}
							if( isset($wooAttributeValue) && !empty($wooAttributeValue) ) {
								$value = $wooAttributeValue;
							}
							else {
								$value = get_post_meta( $product_id, $metaKey, true );
							}
						}
						else {
							$value = get_post_meta( $product_id, $metaKey, true );
						}
						
					}
					else 
					{
						$value = get_post_meta( $product_id, $tempProfileData['metakey'], true );
						if($tempProfileData['metakey'] == '_thumbnail_id'){
							$value = wp_get_attachment_image_url( get_post_meta( $product_id,'_thumbnail_id',true), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $product_id,'_thumbnail_id',true), 'thumbnail' ) : '';
						}
						if( !isset($value) || empty($value) || $value == "" || is_null($value) || $value == "0" || $value == "null") {
							if( $parentId != "0" ) {
	
								$value = get_post_meta( $parentId, $tempProfileData['metakey'], true );
								if($tempProfileData['metakey'] == '_thumbnail_id'){
									$value = wp_get_attachment_image_url( get_post_meta( $parentId,'_thumbnail_id',true), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $parentId,'_thumbnail_id',true), 'thumbnail' ) : '';
								}
	
								if( !isset($value) || empty($value) || $value == "" || is_null($value) ) {
									$value = get_post_meta( $product_id, $metaKey, true );
	
								}
							}
							else {
								$value = get_post_meta( $product_id, $metaKey, true );
							}
						}
	
					}
				}
				else {
					$value = get_post_meta( $product_id, $metaKey, true );
				}
			}
			else {
				$value = get_post_meta( $product_id, $metaKey, true );
			}
		}
		else {
			$value = get_post_meta( $product_id, $metaKey, true );
		}
	
		return $value;
	}
	
	/**
	 * This function formats php array in SIMPLE_XML_ELEMENT object.
	 * @name array2XML()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function array2XML($xml_obj, $array) {
		foreach ($array as $key => $value) {
			if(is_numeric($key)) {
				$key = $key;
			}
			if (is_array($value)) {
				$node = $xml_obj->addChild($key);
				$this->array2XML($node, $value);
			}
			else {
				$xml_obj->addChild($key, htmlspecialchars($value));
			}
		}
	}
	
	/**
	 * This function formats product xml in correct format.
	 * @name formatAndAppendDataToXmlString()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function formatAndAppendDataToXmlString( $key, $arrayToUse, $mainXMLString , $isVariation=false, $stringToUse='', $assetStringToUse = "", $additionalProductAttributesStringToUse = "" ) {
		
		$xml = new SimpleXMLElement($key);
		$this->array2XML($xml, $arrayToUse);
		
		if(isset( $arrayToUse['Product']['longDescription'] ))
		{	
			$xml->Product->longDescription = NULL;
			$node1 = dom_import_simplexml($xml->Product->longDescription);
			$no   = $node1->ownerDocument;
			$node1->appendChild($no->createCDATASection($arrayToUse['Product']['longDescription']));
		}
		
		if(isset($arrayToUse['Product']['shelfDescription']))
		{
			$xml->Product->shelfDescription = NULL;
			$node1 = dom_import_simplexml($xml->Product->shelfDescription);
			$no   = $node1->ownerDocument;
			$node1->appendChild($no->createCDATASection($arrayToUse['Product']['shelfDescription']));
		}
		
		if(isset($arrayToUse['Product']['shortDescription']))
		{
			$xml->Product->shortDescription = NULL;
			$node1 = dom_import_simplexml($xml->Product->shortDescription);
			$no   = $node1->ownerDocument;
			$node1->appendChild($no->createCDATASection($arrayToUse['Product']['shortDescription']));
		}
		
		$val = $xml->asXML();
		if($isVariation) {
			$val  = $this->handleVariantAttributeNamesConditionInXMLString($val,$stringToUse);
		}
		
		if( $assetStringToUse != '') {
			$str = $this->get_string_between($val, '<additionalAssets>', '</additionalAssets>');
			$str = '<additionalAssets>'.$str.'</additionalAssets>';
			$val = str_replace($str, $assetStringToUse, $val);
		}

		if( $additionalProductAttributesStringToUse != "" ) {
			$str = $this->get_string_between($val, '<additionalProductAttributes>', '</additionalProductAttributes>');
			$str = '<additionalProductAttributes>'.$str.'</additionalProductAttributes>';
			$val = str_replace($str, $additionalProductAttributesStringToUse, $val);
		}
		
		$val = $this->removeXMLTagFromXMLString($val);
		$mainXMLString .= $val;
		return $mainXMLString;
	}
	
	/**
	 * This function removes uncessary tags that creates issue in making xml.
	 * @name removeXMLTagFromXMLString()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function removeXMLTagFromXMLString($xmlString) {
		$str = $this->get_string_between($xmlString, '<?', '?>');
		$str = '<?'.$str.'?>';
		$xmlString = str_replace($str, '', $xmlString);
		return $xmlString;
	}
	
	/**
	 * This function gets substring between to string chunks.
	 * @name get_string_between()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function get_string_between($string, $start, $end){
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}
	
	/**
	 * This function fetches data in accordance with profile assigned to product.
	 * @name fetchAssignedProfileDataOfProduct()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function fetchAssignedProfileDataOfProduct( $product_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix.ced_umb_amazon_PREFIX.'profiles';
		$profileID = get_post_meta( $product_id, 'ced_umb_amazon_profile', true);
		$profile_data = array();
		if( isset($profileID) && !empty($profileID) && $profileID != "" ) {
			$this->isProfileAssignedToProduct = true;
			$profileid = $profileID;
			$query = "SELECT * FROM `$table_name` WHERE `id`=$profileid";
			$profile_data = $wpdb->get_results($query,'ARRAY_A');
			if(is_array($profile_data)) {
				$profile_data = isset($profile_data[0]) ? $profile_data[0] : $profile_data;
				$profile_data = isset($profile_data['profile_data']) ? json_decode($profile_data['profile_data'],true) : array();
			}
		}
		else {
			$this->isProfileAssignedToProduct = false;
		}
		$this->profile_data = $profile_data;
	}
	
	/**
	 * This function make array to be places at MPItem key in product update xml.
	 * @name makeArrayFor_MPITEM()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link  http://www.cedcommerce.com/
	 */
	function makeArrayFor_MPITEM($product_id,$isPrimaryVariant = "false", $kMessageID) {
		
		$product = wc_get_product( $product_id );
		$productType = $product->get_type();
		if($productType == 'simple')
		{
			$productxmlarray = $this->makexmlArrayforsimpleproduct($product_id,$isPrimaryVariant, $kMessageID);
		}	
		
		return $productxmlarray;
	}
		
	function makexmlArrayforsimpleproduct($product_id,$isPrimaryVariant, $kMessageID)
	{
		$this->fetchAssignedProfileDataOfProduct( $product_id );
		$assignedamazonCategory = $this->fetchMetaValueOfProduct( $product_id, '_umb_amazon_category' );
		$woo_ver = WC()->version;
		if(isset($assignedamazonCategory) && !empty($assignedamazonCategory))
		{
			$amazonxmlarray = array();
			$amazonxmlarray['MessageID'] = $kMessageID;
			$amazonxmlarray['OperationType'] = "Update";
				
			$product = new WC_Product($product_id);
			if($woo_ver < "3.0.0" && $woo_ver < "2.7.0")
			{
				$post_data = $product->get_post_data();
			}
			else
			{
				$post_data = $product->get_data();				
			}
			$amazonJsonFileName = 'amazon-product.json';
			$productattributes = $this->readamazonInfoFromJsonFile( $amazonJsonFileName );
			$amazonJsonFileName = 'amazon-category.json';
				
			$productattributexml = array();
			foreach($productattributes as $k=>$productattribute)
			{
				if(empty($productattribute['name']))
				{
					$name = $productattribute['ref'];
				}
				else
				{
					$name = $productattribute['name'];
				}
		
				if($productattribute['name'] != 'ProductData')
				{
					if(!empty($productattribute['value']))
					{
						$field = array();
						$descriptiondatavalues = $productattribute['value'];
						if (count($descriptiondatavalues) == count($descriptiondatavalues, COUNT_RECURSIVE))
						{
							$metakey = "_umb_amazon_".$name;
							
							$metavalue = $this->fetchMetaValueOfProduct( $product_id, $metakey );
							
							//$metavalue = get_post_meta($product_id, $metakey, true);
							if(isset($metavalue) && !empty($metavalue))
							{
								$productattributexml[$name] = $metavalue;
							}
						}
						else
						{
							$parentname = $productattribute['name'];
							$name = $productattribute['name'];
							$subcatarray = array();
							foreach($descriptiondatavalues as $key1=>$descriptiondatavalue)
							{
								$subcatarray = array();
								if (count($descriptiondatavalue) == count($descriptiondatavalue, COUNT_RECURSIVE))
								{
									$name = $descriptiondatavalue['name'];
									$matchexisted = true;
									if($parentname == "DescriptionData" && $name == "Title")
									{
										if($isPrimaryVariant)
										{
											$metavalue = get_post_meta($product_id, "_umb_variation_title", true);
										}
										else
										{
											$metavalue = $product->get_title();
										}		
										$productattributexml[$parentname][$name] = $metavalue;
										$matchexisted = false;
									}
										
									if($parentname == "DescriptionData" && $name == "Brand")
									{
										$metavalue = $this->fetchMetaValueOfProduct( $product_id, "_amazon_umb_brand" );
										$productattributexml[$parentname][$name] = $metavalue;
										$matchexisted = false;
									}
										
									if($parentname == "DescriptionData" && $name == "Description")
									{
										if($woo_ver < "3.0.0" && $woo_ver < "2.7.0")
										{
											$metavalue = $product->post->post_content;
										}
										else
										{
											$metavalue = $product->get_data()['description'];
										}
										$productattributexml[$parentname][$name] = $metavalue;
										$matchexisted = false;
									}
										
									if($parentname == "StandardProductID" && $name == "Value")
									{
										$metavalue = $this->fetchMetaValueOfProduct( $product_id, "_amazon_umb_id_val" );
										$productattributexml[$parentname][$name] = $metavalue;
										$matchexisted = false;
									}
									
									if($parentname == "DescriptionData" && $name == "Manufacturer")
									{
										$metavalue = $this->fetchMetaValueOfProduct( $product_id, "_amazon_umb_manufacturer" );
										$productattributexml[$parentname][$name] = $metavalue;
										$matchexisted = false;
									}
									
									if($parentname == "DescriptionData" && $name == "MfrPartNumber")
									{
										$metavalue = $this->fetchMetaValueOfProduct( $product_id, "_amazon_umb_mpr" );
										$productattributexml[$parentname][$name] = $metavalue;
										$matchexisted = false;
									}
									
									if($parentname == "DescriptionData" && $name == "BulletPoint")
									{
										$metakey = "_umb_amazon_".$parentname."_".$name;
										$metavalue = $this->fetchMetaValueOfProduct( $product_id, $metakey );
										
										$metavaluedatas = explode("|", $metavalue);
										$i = 0;
										foreach($metavaluedatas as $metavaluedata)
										{
											$i++;
											$productattributexml[$parentname][$name][] = $metavaluedata;
											if($i == 5)
											{
												break;
											}	
										}	
										$i = 0;
										
										$matchexisted = false;
									}
									
									if($parentname == "DescriptionData" && $name == "SearchTerms")
									{
										$metakey = "_umb_amazon_".$parentname."_".$name;
										$metavalue = $this->fetchMetaValueOfProduct( $product_id, $metakey );
										
										$metavaluedatas = explode("|", $metavalue);
										$i = 0;
										foreach($metavaluedatas as $metavaluedata)
										{
											$i++;
											$productattributexml[$parentname][$name][] = $metavaluedata;
											if($i == 5)
											{
												break;
											}	
										}	
										$i = 0;	
										$matchexisted = false;
									}
									
									if($matchexisted)
									{
										$metakey = "_umb_amazon_".$parentname."_".$name;
										$metavalue = $this->fetchMetaValueOfProduct( $product_id, $metakey );
										//$metavalue = get_post_meta($product_id, $metakey, true);
		
										if(isset($metavalue) && !empty($metavalue))
										{
											if ((strpos($name, 'Dimension') !== false) || (strpos($name, 'BaseCurrencyAmount') !== false)  || (strpos($name, 'positiveInteger') !== false)) {
												$productattributexml[$parentname]['@value'] = $metavalue;
													
											}
											else
											{
												if((strpos($name, 'unitOfMeasure') !== false) || (strpos($name, 'currency') !== false))
												{
													$productattributexml[$parentname]['@attributes'][$name] = $metavalue;
												}
												else
												{
													$productattributexml[$parentname][$name] = $metavalue;
												}
											}
										}
									}
								}
								else
								{
									if(isset($descriptiondatavalue['name']))
									{
										$reach = true;
										if(isset($descriptiondatavalue['value']))
										{
											$submetavalues = $descriptiondatavalue['value'];
											if (count($submetavalues) == count($submetavalues, COUNT_RECURSIVE))
											{
		
											}
											else
											{
												$reach = false;
												$name = $descriptiondatavalue['name'];
												foreach($submetavalues as $submetavalue)
												{
													if(isset($submetavalue['value']))
													{	
														$subparentname = $submetavalue['name'];
														if (count($submetavalue['value']) == count($submetavalue['value'], COUNT_RECURSIVE))
														{
															$subcatarray = array();
															if(isset($submetavalue['name']))
															{
																$subparentname = $submetavalue['name'];
																$metakey = "_umb_amazon_".$parentname."_".$name."_".$subparentname;
																
																$metavalue = $this->fetchMetaValueOfProduct( $product_id, $metakey );
																
															//	$metavalue = get_post_meta($product_id, $metakey, true);
			
																if(isset($metavalue) && !empty($metavalue))
																{
																	if ((strpos($subparentname, 'Dimension') !== false) || (strpos($subparentname, 'BaseCurrencyAmount') !== false) || (strpos($subparentname, 'positiveInteger') !== false)) {
																		$productattributexml[$parentname][$name]['@value'] = $metavalue;
																	}
																	else{
																		if((strpos($subparentname, 'unitOfMeasure') !== false) || (strpos($subparentname, 'currency') !== false))
																		{
																			$productattributexml[$parentname][$name]['@attributes'][$subparentname] = $metavalue;
																		}
																		else
																		{
																			$productattributexml[$parentname][$name][$subparentname] = $metavalue;
																		}
																			
																	}
																}
															}
														}
														else
														{
															$subsubmetavalues = $submetavalue['value'];
															foreach($subsubmetavalues as $subsubmetavalue)
															{
																$subsubparentname = $subsubmetavalue['name'];
																$metakey = "_umb_amazon_".$parentname."_".$name."_".$subparentname."_".$subsubparentname;
																
																$metavalue = $this->fetchMetaValueOfProduct( $product_id, $metakey );
																
																//$metavalue = get_post_meta($product_id, $metakey, true);
			
																if(isset($metavalue) && !empty($metavalue))
																{
																	if ((strpos($subsubparentname, 'Dimension') !== false) || (strpos($subsubparentname, 'BaseCurrencyAmount') !== false) || (strpos($subsubparentname, 'positiveInteger') !== false)) {
																		$productattributexml[$parentname][$name][$subparentname]['@value'] = $metavalue;
			
																	}
																	else{
																		if((strpos($subsubparentname, 'unitOfMeasure') !== false) || (strpos($subsubparentname, 'currency') !== false))
																		{
																			$productattributexml[$parentname][$name][$subparentname]['@attributes'][$subsubparentname] = $metavalue;
																		}
																		else
																		{
																			$productattributexml[$parentname][$name][$subparentname][$subsubparentname] = $metavalue;
																		}
																	}
																}
															}
														}
													}
												}
											}
										}
		
										if($reach)
										{
											$name = $descriptiondatavalue['name'];
		
											if($parentname == "StandardProductID" && $name == "Type")
											{
												$metavalue = $this->fetchMetaValueOfProduct( $product_id, "_amazon_umb_id_type" );
												
												$productattributexml[$parentname][$name] = $metavalue;
											}
											else
											{
												$metakey = "_umb_amazon_".$parentname."_".$name;
												
												
												$metavalue = $this->fetchMetaValueOfProduct( $product_id, $metakey );
													
												if(isset($metavalue) && !empty($metavalue))
												{
													if ((strpos($name, 'Dimension') !== false) || (strpos($name, 'BaseCurrencyAmount') !== false) || (strpos($name, 'positiveInteger') !== false)) {
														$productattributexml[$parentname]['@value'] = $metavalue;
															
													}
													else{
														if((strpos($name, 'unitOfMeasure') !== false) || (strpos($name, 'currency') !== false))
														{
															$productattributexml[$parentname]['@attributes'][$name] = $metavalue;
														}
														else
														{
															$productattributexml[$parentname][$name] = $metavalue;
														}
															
													}
												}
											}
										}
									}
								}
							}
						}
					}
					else
					{
						$offset_end = $this->getStandardOffsetUTC(); // get offset
						if (empty($offset_end) || trim($offset_end) == '')
						{
							$offset = '.0000000-00:00';
						}
						else
						{
							$offset = '.0000000' . trim($offset_end);
						}
						
						$timestamp = date("Y-m-d", time()) . 'T' . date("H:i:s", time()) . $offset;
						$matchdesciption = true;
						if($name == "SKU")
						{
							$productattributexml[$name] = $product->get_sku();
							$matchdesciption = false;
						}
						if($name == "LaunchDate")
						{
							$productattributexml[$name] = $timestamp;
							$matchdesciption = false;
						}
						if($name == "DiscontinueDate")
						{
							$productattributexml[$name] = $timestamp;
							$matchdesciption = false;
						}
						if($matchdesciption)
						{
							$metakey = "_umb_amazon_".$name;
							
							$metavalue = $this->fetchMetaValueOfProduct( $product_id, $metakey );
							
							//$metavalue = get_post_meta($product_id, $metakey, true);
							if(isset($metavalue) && !empty($metavalue))
							{
								$productattributexml[$name] = $metavalue;
							}
						}
					}
				}
				else
				{
					$productattributexml['ProductData'][$assignedamazonCategory] = array();
				}
			}
				
			$categoryattributes = $this->readamazonInfoFromJsonFile( $amazonJsonFileName );
			$assignedcategoryattributes = $categoryattributes[$assignedamazonCategory]['value'];
				
			$xmlcategryarray = array();
				
			foreach($assignedcategoryattributes as $assignedcategoryattribute )
			{
				$assignedcategoryattributeparentname = $assignedcategoryattribute['name'];
				
				if($assignedcategoryattributeparentname == "ProductType")
				{
					
					$assignedcategoryattributeparentname = $product_id."_producttype";
					$assignedcategoryattributeparentnamekey = "ProductType";
						
					$meta_key = $assignedamazonCategory."_".$product_id."_ced_umb_amazon_amazon_".$assignedamazonCategory."_".$assignedcategoryattributeparentnamekey;
					//$selected_producttype_value = get_post_meta($product_id, $meta_key, true);
					
					$selected_producttype_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
					
					if(isset($selected_producttype_value) && !empty($selected_producttype_value))
					{	
						$xmlcategryarray[$assignedcategoryattributeparentnamekey][$selected_producttype_value] = array();
					}
					else
					{
						continue;
					}		
				}
				else
				{
					$assignedcategoryattributeparentnamekey = $assignedcategoryattributeparentname;
				}
				
				if(isset($assignedcategoryattribute['value']))
				{
					if(count($assignedcategoryattribute['value']) == count($assignedcategoryattribute['value'], COUNT_RECURSIVE))
					{
						$assignedcategoryattributeparentname = $assignedcategoryattribute['name'];
						$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname;
						
						$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
						
						//$meta_value = get_post_meta($product_id, $meta_key, true);
						if(isset($meta_value) && !empty($meta_value))
						{
							$xmlcategryarray[$assignedcategoryattributeparentnamekey] = $meta_value;
						}
					}
					else
					{
						$assignedcategoryattributechilds = $assignedcategoryattribute['value'];
						foreach($assignedcategoryattributechilds as $assignedcategoryattributechild)
						{
							if(isset($assignedcategoryattributechild['value']))
							{
								$assignedcategoryattributeparentchildname = $assignedcategoryattributechild['name'];
								if(count($assignedcategoryattributechild['value']) == count($assignedcategoryattributechild['value'], COUNT_RECURSIVE))
								{
									$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname;
									//echo "<br/>";
									$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
									
									//$meta_value = get_post_meta($product_id, $meta_key, true);
									if(isset($meta_value) && !empty($meta_value))
									{
										if ((strpos($assignedcategoryattributeparentchildname, 'Dimension') !== false) || (strpos($assignedcategoryattributeparentchildname, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributeparentchildname, 'positiveInteger') !== false))
										{
											$xmlcategryarray[$assignedcategoryattributeparentnamekey]['@value'] = $meta_value;
										}
										else
										{
											if((strpos($assignedcategoryattributeparentchildname, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributeparentchildname, 'currency') !== false))
											{
												$xmlcategryarray[$assignedcategoryattributeparentnamekey]['@attributes'][$assignedcategoryattributeparentchildname] = $meta_value;
											}
											else
											{
												$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname] = $meta_value;
											}
										}
											
									}
								}
								else
								{
									$assignedcategoryattributechildrens = $assignedcategoryattributechild['value'];
										
									foreach($assignedcategoryattributechildrens as $assignedcategoryattributechildren)
									{
										if(isset($assignedcategoryattributechildren['value']))
										{
											$assignedcategoryattributeparentchildrenname = $assignedcategoryattributechildren['name'];
											if(count($assignedcategoryattributechildren['value']) == count($assignedcategoryattributechildren['value'], COUNT_RECURSIVE))
											{
												$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname."_".$assignedcategoryattributeparentchildrenname;
												//$meta_value = get_post_meta($product_id, $meta_key, true);
												
												$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
												
												if(isset($meta_value) && !empty($meta_value))
												{
													if ((strpos($assignedcategoryattributeparentchildrenname, 'Dimension') !== false) || (strpos($assignedcategoryattributeparentchildrenname, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributeparentchildrenname, 'positiveInteger') !== false))
													{
														$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname]['@value'] = $meta_value;
													}
													else
													{
														if((strpos($assignedcategoryattributeparentchildrenname, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributeparentchildrenname, 'currency') !== false))
														{
															$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname]['@attributes'][$assignedcategoryattributeparentchildrenname] = $meta_value;
														}
														else
														{
															$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname] = $meta_value;
														}
													}
												}
											}
											else
											{
												$assignedcategoryattributesubchildrens = $assignedcategoryattributechildren['value'];
		
												foreach($assignedcategoryattributesubchildrens as $assignedcategoryattributesubchildren)
												{
													if(isset($assignedcategoryattributesubchildren['value']))
													{
														$assignedcategoryattributeparentsubchildrenname = $assignedcategoryattributesubchildren['name'];
		
														if(count($assignedcategoryattributesubchildren['value']) == count($assignedcategoryattributesubchildren['value'], COUNT_RECURSIVE))
														{
															$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname."_".$assignedcategoryattributeparentchildrenname."_".$assignedcategoryattributeparentsubchildrenname;
															//$meta_value = get_post_meta($product_id, $meta_key, true);

															$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
															
															if(isset($meta_value) && !empty($meta_value))
															{
																if ((strpos($assignedcategoryattributeparentsubchildrenname, 'Dimension') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'positiveInteger') !== false))
																{
																	$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname]['@value'] = $meta_value;
																}
																else
																{
																	if((strpos($assignedcategoryattributeparentsubchildrenname, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'currency') !== false))
																	{
																		$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname]['@attributes'][$assignedcategoryattributeparentsubchildrenname] = $meta_value;
																	}
																	else
																	{
																		$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname] = $meta_value;
																	}
																}
															}
														}
														else
														{
															$assignedcategoryattributesubchildrenvalues = $assignedcategoryattributesubchildren['value'];
																
															foreach($assignedcategoryattributesubchildrenvalues as $assignedcategoryattributesubchildrenvalue)
															{
																$assignedcategoryattributesubchildrenvaluename = $assignedcategoryattributesubchildrenvalue['name'];
																if(isset($assignedcategoryattributesubchildrenvalue['value']))
																{
																	$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname."_".$assignedcategoryattributeparentchildrenname."_".$assignedcategoryattributeparentsubchildrenname."_".$assignedcategoryattributesubchildrenvaluename;
																	//$meta_value = get_post_meta($product_id, $meta_key, true);

																	$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
																	
																	if(isset($meta_value) && !empty($meta_value))
																	{
																		if ((strpos($assignedcategoryattributesubchildrenvaluename, 'Dimension') !== false) || (strpos($assignedcategoryattributesubchildrenvaluename, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributesubchildrenvaluename, 'positiveInteger') !== false))
																		{
																			$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname]['@value'] = $meta_value;
																		}
																		else
																		{
																			if((strpos($assignedcategoryattributesubchildrenvaluename, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributesubchildrenvaluename, 'currency') !== false))
																			{
																				$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname][$assignedcategoryattributesubchildrenvaluename]['@attributes'][$assignedcategoryattributesubchildrenvaluename] = $meta_value;
																			}
																			else
																			{
																				$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname][$assignedcategoryattributesubchildrenvaluename] = $meta_value;
																			}
																		}
																	}
																}
																else
																{
																	$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname."_".$assignedcategoryattributeparentchildrenname."_".$assignedcategoryattributeparentsubchildrenname."_".$assignedcategoryattributesubchildrenvaluename;
																//	$meta_value = get_post_meta($product_id, $meta_key, true);

																	$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
																	
																	if(isset($meta_value) && !empty($meta_value))
																	{
																		if ((strpos($assignedcategoryattributesubchildrenvaluename, 'Dimension') !== false) || (strpos($assignedcategoryattributesubchildrenvaluename, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributesubchildrenvaluename, 'positiveInteger') !== false))
																		{
																			$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname]['@value'] = $meta_value;
																		}
																		else
																		{
																			if((strpos($assignedcategoryattributesubchildrenvaluename, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributesubchildrenvaluename, 'currency') !== false))
																			{
																				$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname][$assignedcategoryattributesubchildrenvaluename]['@attributes'][$assignedcategoryattributesubchildrenvaluename] = $meta_value;
																			}
																			else
																			{
																				$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname][$assignedcategoryattributesubchildrenvaluename] = $meta_value;
																			}
																		}
																			
																			
																	}
																}
															}
		
															$assignedcategoryattributeparentsubchildrenname = $assignedcategoryattributesubchildren['name'];
															$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname."_".$assignedcategoryattributeparentchildrenname."_".$assignedcategoryattributeparentsubchildrenname;
															//$meta_value = get_post_meta($product_id, $meta_key, true);
															
															$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
															
															if(isset($meta_value) && !empty($meta_value))
															{
																	
																if ((strpos($assignedcategoryattributeparentsubchildrenname, 'Dimension') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'positiveInteger') !== false) )
																{
																	$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname]['@value'] = $meta_value;
																}
																else
																{
																	if((strpos($assignedcategoryattributeparentsubchildrenname, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'currency') !== false))
																	{
																		$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname]['@attributes'][$assignedcategoryattributeparentsubchildrenname] = $meta_value;
																	}
																	else
																	{
																		$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname] = $meta_value;
																	}
																}
																	
															}
														}
													}
													else
													{
														$assignedcategoryattributeparentsubchildrenname = $assignedcategoryattributesubchildren['name'];
		
														$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname."_".$assignedcategoryattributeparentchildrenname."_".$assignedcategoryattributeparentsubchildrenname;
														//$meta_value = get_post_meta($product_id, $meta_key, true);
														
														$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
														
														if(isset($meta_value) && !empty($meta_value))
														{
																
															if ((strpos($assignedcategoryattributeparentsubchildrenname, 'Dimension') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'positiveInteger') !== false))
															{
																$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname]['@value'] = $meta_value;
															}
															else
															{
																if((strpos($assignedcategoryattributeparentsubchildrenname, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributeparentsubchildrenname, 'currency') !== false))
																{
																	$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname]['@attributes'][$assignedcategoryattributeparentsubchildrenname] = $meta_value;
																}
																else
																{
																	$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname][$assignedcategoryattributeparentsubchildrenname] = $meta_value;
																}
															}
		
														}
													}
												}
											}
										}
										else
										{
											$assignedcategoryattributeparentchildrenname = $assignedcategoryattributechildren['name'];
												
											$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname."_".$assignedcategoryattributeparentchildrenname;
											
											$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
											
											if(isset($meta_value) && !empty($meta_value))
											{
												if(isset($meta_value) && !empty($meta_value))
												{
													if ((strpos($assignedcategoryattributeparentchildrenname, 'Dimension') !== false) || (strpos($assignedcategoryattributeparentchildrenname, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributeparentchildrenname, 'positiveInteger') !== false))
													{
														$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname]['@value'] = $meta_value;
													}
													else
													{
														if((strpos($assignedcategoryattributeparentchildrenname, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributeparentchildrenname, 'currency') !== false))
														{
															$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname]['@attributes'][$assignedcategoryattributeparentchildrenname] = $meta_value;
														}
														else
														{
															$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname][$assignedcategoryattributeparentchildrenname] = $meta_value;
														}
													}
												}
													
											}
										}
									}
								}
							}
							else
							{
								$assignedcategoryattributeparentchildname = $assignedcategoryattributechild['name'];
								$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname."_".$assignedcategoryattributeparentchildname;
								//$meta_value = get_post_meta($product_id, $meta_key, true);
								
								$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
								
								if(isset($meta_value) && !empty($meta_value))
								{
									if ((strpos($assignedcategoryattributeparentchildname, 'Dimension') !== false) || (strpos($assignedcategoryattributeparentchildname, 'BaseCurrencyAmount') !== false) || (strpos($assignedcategoryattributeparentchildname, 'positiveInteger') !== false))
									{
										$xmlcategryarray[$assignedcategoryattributeparentnamekey]['@value'] = $meta_value;
									}
									else
									{
										if((strpos($assignedcategoryattributeparentchildname, 'unitOfMeasure') !== false) || (strpos($assignedcategoryattributeparentchildname, 'currency') !== false))
										{
											$xmlcategryarray[$assignedcategoryattributeparentnamekey]['@attributes'][$assignedcategoryattributeparentchildname] = $meta_value;
										}
										else
										{
											$xmlcategryarray[$assignedcategoryattributeparentnamekey][$assignedcategoryattributeparentchildname] = $meta_value;
										}
									}
								}
							}
						}
					}
				}
				else
				{
					$meta_key = $assignedamazonCategory."_".$assignedcategoryattributeparentname;
					//$meta_value = get_post_meta($product_id, $meta_key, true);
					
					$meta_value = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
					
					if(isset($meta_value) && !empty($meta_value))
					{
						$xmlcategryarray[$assignedcategoryattributeparentnamekey] = $meta_value;
					}
				}
			}
			
			if(isset($xmlcategryarray['ProductType']))
			{
				if(is_array($xmlcategryarray['ProductType']))
				{				
					foreach($xmlcategryarray['ProductType'] as $key=>$value)
					{
						if($key !== $selected_producttype_value)
						{
							unset($xmlcategryarray['ProductType'][$key]);
						}
					}
				}
			}
				
			$productattributexml['ProductData'][$assignedamazonCategory] = $xmlcategryarray;
			$amazonxmlarray = array();
			$amazonxmlarray['MessageID'] = $kMessageID;
			$amazonxmlarray['OperationType'] = "Update";
			$amazonxmlarray['Product'] = $productattributexml;
			return $amazonxmlarray;
		}
	}
	
	/**
	 * Get Time Zone
	 */
	public function getStandardOffsetUTC()
	{
		$timezone = date_default_timezone_get();
	
		if($timezone == 'UTC') {
			return '';
		} else {
			$timezone = new DateTimeZone($timezone);
			$transitions = array_slice($timezone->getTransitions(), -3, null, true);
	
			foreach (array_reverse($transitions, true) as $transition)
			{
				if ($transition['isdst'] == 1)
				{
					continue;
				}
				return sprintf('UTC %+03d:%02u', $transition['offset'] / 3600, abs($transition['offset']) % 3600 / 60);
			}
	
			return false;
		}
	}
}	
	