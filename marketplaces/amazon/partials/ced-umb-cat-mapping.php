<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$amazonCategoryPath = 'json/amazon-category.json';
$amazonCategoryPath = ced_umb_amazon_DIRPATH.'marketplaces/'.$marketplace.'/partials/'.$amazonCategoryPath;
ob_start();
readfile($amazonCategoryPath);
$json_data = ob_get_clean();
$categories = json_decode($json_data, TRUE);
$selectedamazonCategories = get_option('ced_umb_amazon_selected_amazon_categories');
if(isset($selectedamazonCategories) && !empty($selectedamazonCategories)) {
	$selectedamazonCategories = json_decode($selectedamazonCategories,TRUE);
}

?>
<div class="ced_umb_amazon_amazon_cat_mapping ced_umb_amazon_toggle_wrapper">
	<div class="ced_umb_amazon_toggle_section">
		<div class="ced_umb_amazon_toggle">
			<h2><?php _e('Amazon','ced-amazon-lister');?></h2>
		</div>
		<div class="ced_umb_amazon_cat_activate_ul ced_umb_amazon_toggle_div">
		<?php 
		$breakPoint = floor(count($categories)/3);
		$counter = 0;
		
		sksort_temp($categories, "name", true);
		
		foreach ($categories as $key => $category) {
			if( $counter == 0 ) {
				echo '<ul class="ced_amazon_cat_ul">';
			}
			$catName = $category['name'];
			if(is_array($selectedamazonCategories) && array_key_exists($category['name'],$selectedamazonCategories)) {
				echo '<li><input type="checkbox" class="ced_umb_amazon_amazon_cat_select" name="'.$catName.'" value="'.$catName.'" checked >'.$catName."</li>";
			}
			else {
				echo '<li><input type="checkbox" class="ced_umb_amazon_amazon_cat_select" name="'.$catName.'" value="'.$catName.'">'.$catName."</li>";
			}

			if( $counter == $breakPoint ) {
				$counter = 0;
				echo '</ul>';
			}
			else{
				$counter++;
			}
		}
		?>
		</div>
	</div>
</div>

<?php
function sksort_temp(&$array, $subkey="id", $sort_ascending=false) {

    if (count($array))
        $temp_array[key($array)] = array_shift($array);

    foreach($array as $key => $val){
        $offset = 0;
        $found = false;
        foreach($temp_array as $tmp_key => $tmp_val)
        {
            if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
            {
                $temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
                                            array($key => $val),
                                            array_slice($temp_array,$offset)
                                          );
                $found = true;
            }
            $offset++;
        }
        if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
    }

    if ($sort_ascending) $array = array_reverse($temp_array);

    else $array = $temp_array;
}
?>