<?php
defined('ABSPATH')||exit;
/** Read the original mandatory fixed upload charge without changing product prices. */
function sp_custom_fee($product_id){
 static $cache=array();$p=wc_get_product($product_id);if(!$p)return new WP_Error('custom_price','This piece is unavailable.');$id=$p->get_parent_id()?:$p->get_id();if(isset($cache[$id]))return$cache[$id];$fee=0;$resolved=function_exists('product_addons')?array_map('absint',product_addons()->get_product_option_ids($id)):null;
 foreach(get_posts(array('post_type'=>'prad_option','post_status'=>'publish','numberposts'=>-1))as$form){
  $assign=json_decode(get_post_meta($form->ID,'prad_base_assigned_data',true),true);
  if(null!==$resolved){if(!in_array((int)$form->ID,$resolved,true))continue;}elseif('specific_product'!==($assign['aType']??'')||!in_array($id,array_map('absint',$assign['includes']??array()),true)||in_array($id,array_map('absint',$assign['excludes']??array()),true))continue;
  foreach((array)get_post_meta($form->ID,'prad_addons_blocks',true)as$block){
   if(!empty($block['hide'])||'heading'===($block['type']??''))continue;
   if('upload'!==($block['type']??'')||empty($block['required'])||count($block['_options']??array())!==1)return new WP_Error('custom_price','Please send a request so we can confirm the options and price for this piece.');
   $option=$block['_options'][0];if('fixed'!==($option['type']??'')||!is_numeric($option['regular']??null))return new WP_Error('custom_price','Please send a request to confirm this piece’s customization price.');
   $amount=isset($option['sale'])&&''!==$option['sale']&&is_numeric($option['sale'])?$option['sale']:$option['regular'];$fee+=(float)$amount;
  }
 }
 return $cache[$id]=$fee;
}
function sp_custom_price_html($product,$base=null){
 $fee=sp_custom_fee($product->get_id());if(is_wp_error($fee))return'Price confirmed by request';
 if(null!==$base)return wc_price(wc_get_price_to_display($product,array('price'=>(float)$base+$fee)));
 if($product->is_type('variable')){$min=wc_get_price_to_display($product,array('price'=>(float)$product->get_variation_price('min')+$fee));$max=wc_get_price_to_display($product,array('price'=>(float)$product->get_variation_price('max')+$fee));return$min===$max?wc_price($min):wc_format_price_range($min,$max);}
 return wc_price(wc_get_price_to_display($product,array('price'=>(float)$product->get_price()+$fee)));
}
add_action('woocommerce_before_calculate_totals',function($cart){if(is_admin()&&!wp_doing_ajax())return;foreach($cart->get_cart()as$item){if(empty($item['sp_custom']))continue;$base=wc_get_product($item['variation_id']?:$item['product_id']);$fee=sp_custom_fee($item['product_id']);if($base&&!is_wp_error($fee))$item['data']->set_price((float)$base->get_price('edit')+$fee);}},100);
add_filter('woocommerce_get_item_data',function($items,$item){if(!empty($item['sp_custom'])){$fee=sp_custom_fee($item['product_id']);if(!is_wp_error($fee)&&$fee>0)$items[]=array('key'=>'Customization included','value'=>wp_strip_all_tags(wc_price($fee)).' per piece');}return$items;},15,2);
add_action('woocommerce_checkout_create_order_line_item',function($item,$key,$values){if(!empty($values['sp_custom'])){$fee=sp_custom_fee($values['product_id']);if(!is_wp_error($fee)&&$fee>0)$item->add_meta_data('Customization included per piece',wc_format_decimal($fee),true);}},15,3);
add_filter('woocommerce_structured_data_product',function($markup,$product){
 if(!sp_custom_product_allowed($product->get_id()))return$markup;$fee=sp_custom_fee($product->get_id());if(is_wp_error($fee)||!$fee||empty($markup['offers']))return$markup;
 $display_fee=wc_get_price_to_display($product,array('price'=>$fee));
 $adjust=function($data)use(&$adjust,$display_fee){foreach($data as$key=>$value){if(is_array($value))$data[$key]=$adjust($value);elseif(in_array($key,array('price','lowPrice','highPrice'),true)&&is_numeric($value))$data[$key]=wc_format_decimal((float)$value+$display_fee,wc_get_price_decimals());}return$data;};$markup['offers']=$adjust($markup['offers']);return$markup;
},20,2);
add_action('woocommerce_check_cart_items',function(){if(!WC()->cart)return;foreach(WC()->cart->get_cart()as$item)if(!empty($item['sp_custom'])&&is_wp_error(sp_custom_fee($item['product_id'])))wc_add_notice('A custom piece needs its options and price confirmed. Remove it from your bag and send a custom request.','error');});
