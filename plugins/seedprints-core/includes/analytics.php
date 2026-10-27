<?php
defined('ABSPATH')||exit;
function sp_analytics_item($p,$quantity=1,$in_cart=false){$fee=!$in_cart&&sp_custom_product_allowed($p->get_id())?sp_custom_fee($p->get_id()):0;$extra=is_wp_error($fee)?0:$fee;return array('item_id'=>(string)($p->get_parent_id()?:$p->get_id()),'item_variant'=>$p->is_type('variation')?(string)$p->get_id():'','item_name'=>$p->get_name(),'price'=>(float)wc_get_price_excluding_tax($p,array('price'=>(float)$p->get_price()+$extra)),'quantity'=>(int)$quantity);}
add_action('wp_footer',function(){
 if('production'!==wp_get_environment_type()||!function_exists('WC'))return;
 $events=array();$currency=get_woocommerce_currency();
 if(is_product()){$p=wc_get_product(get_queried_object_id());if($p)$events[]=array('event'=>'view_item','ecommerce'=>array('currency'=>$currency,'value'=>sp_analytics_item($p)['price'],'items'=>array(sp_analytics_item($p))));}
 if(is_checkout()&&!is_order_received_page()&&WC()->cart){$items=array();foreach(WC()->cart->get_cart()as$item)$items[]=sp_analytics_item($item['data'],$item['quantity'],true);$events[]=array('event'=>'begin_checkout','ecommerce'=>array('currency'=>$currency,'value'=>(float)WC()->cart->get_cart_contents_total(),'items'=>$items));}
 if(is_order_received_page()){
  $order=wc_get_order(absint(get_query_var('order-received')));$key=isset($_GET['key'])&&is_string($_GET['key'])?wc_clean(wp_unslash($_GET['key'])):'';
  if($order&&$order->is_paid()&&$key&&hash_equals($order->get_order_key(),$key)){$items=array();foreach($order->get_items()as$item){$p=$item->get_product();if($p){$data=sp_analytics_item($p,$item->get_quantity());$data['price']=$item->get_quantity()?(float)$item->get_total()/$item->get_quantity():0;$items[]=$data;}}$events[]=array('event'=>'purchase','ecommerce'=>array('transaction_id'=>(string)$order->get_id(),'currency'=>$order->get_currency(),'value'=>(float)($order->get_total()-$order->get_total_tax()-$order->get_shipping_total()),'tax'=>(float)$order->get_total_tax(),'shipping'=>(float)$order->get_shipping_total(),'items'=>$items));}
 }
 if($events)echo'<script id="sp-ecommerce-events" type="application/json">'.wp_json_encode($events,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
},13);
