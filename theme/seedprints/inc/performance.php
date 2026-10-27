<?php
defined('ABSPATH') || exit;

/** Keep the complete jQuery dependency chain together at the footer. */
add_action('wp_print_scripts', function () {
 if (is_admin() || (!is_front_page() && !is_product())) return;
 $scripts=wp_scripts();$memo=array();
 $depends_on_jquery=function($handle)use(&$depends_on_jquery,&$memo,$scripts){
  if(in_array($handle,array('jquery','jquery-core','jquery-migrate'),true))return true;
  if(array_key_exists($handle,$memo))return $memo[$handle];
  $memo[$handle]=false;
  foreach($scripts->registered[$handle]->deps??array()as$dependency)if($depends_on_jquery($dependency))return $memo[$handle]=true;
  return false;
 };
 foreach(array_keys($scripts->registered)as$handle)if($depends_on_jquery($handle))$scripts->add_data($handle,'group',1);
},PHP_INT_MAX);
