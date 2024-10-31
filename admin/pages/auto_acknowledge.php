<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

//header file.
require_once ced_umb_amazon_DIRPATH.'admin/pages/header.php';


?>
<div class="ced_umb_amazon_wrap">
	<div class="meta-box-sortables ui-sortable">
		<div class="ced_umb_amazon_bottom_padding ced_umb_amazon_bottom_margin">
			<h2 class="ced_umb_amazon_setting_header"><?php _e('Amazon Data','ced-amazon-lister');?></h2>
			<span class="ced_umb_amazon_white_txt"><?php _e('Remaining information will be uploaded on amazon .','ced-amazon-lister');?></span>
		</div>
		<div class="ced_umb_amazon_return_address">
				<table class="ced_umb_amazon_return_address wp-list-table widefat fixed striped activityfeeds" >
					<tbody>
						<tr>
							<th><?php _e('Click to upload data on amazon(please make sure got sucess in product upload feed)','ced-amazon-lister');?></th>
							<td>
								<?php 
								$ced_umb_amazon_cron_file_path = CED_MAIN_URL."includes/class-umb-cron.php";
									
								?>
								<button class="ced_umb_amazon_cron"><?php _e('Click','ced-amazon-lister');?></button>
									
							</td>
						</tr>
						
					</tbody>
				</table>
		</div>
	</div>	
</div>
