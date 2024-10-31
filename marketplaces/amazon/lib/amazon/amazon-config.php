<?php
/**
 * Copyright 2013 CPI Group, LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 *
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

$saved_amazon_details = get_option( 'ced_umb_amazon_amazon_configuration', false );
$service_url = isset( $saved_amazon_details['service_url'] ) ? esc_attr( $saved_amazon_details['service_url'] ) : '';
$marketplace_id = isset( $saved_amazon_details['marketplace_id'] ) ? esc_attr( $saved_amazon_details['marketplace_id'] ) : '';
$merchant_id = isset( $saved_amazon_details['merchant_id'] ) ?  $saved_amazon_details['merchant_id']  : "";
$key_id = isset( $saved_amazon_details['key_id'] ) ? esc_attr( $saved_amazon_details['key_id'] ) : '';
$secret_key = isset( $saved_amazon_details['secret_key'] ) ? esc_attr( $saved_amazon_details['secret_key'] ) : '';
$auth_token = isset( $saved_amazon_details['auth_token'] ) ?  $saved_amazon_details['auth_token']  : "";



$store['YourAmazonStore']['merchantId'] = $merchant_id;//Merchant ID for this store
$store['YourAmazonStore']['marketplaceId'] = $marketplace_id; //Marketplace ID for this store
$store['YourAmazonStore']['keyId'] = $key_id; //Access Key ID
$store['YourAmazonStore']['secretKey'] = $secret_key; //Secret Access Key for this store
$store['YourAmazonStore']['serviceUrl'] = $service_url; //optional override for Service URL
$store['YourAmazonStore']['MWSAuthToken'] = $auth_token; //token needed for web apps and third-party developers

//Service URL Base
//Current setting is United States
$AMAZON_SERVICE_URL = $service_url;

//Location of log file to use
$logpath = __DIR__.'/log.txt';

//Name of custom log function to use
$logfunction = '';

//Turn off normal logging
$muteLog = false;

?>
