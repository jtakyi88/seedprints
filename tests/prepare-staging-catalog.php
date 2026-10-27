<?php
if ( 'staging' !== wp_get_environment_type() || 'https://try.seedprints.com' !== home_url() ) { WP_CLI::error('Staging only.'); }
$terms = array( 'product_cat' => array('women'=>'Women','men'=>'Men','youth'=>'Youth','drinkware'=>'Drinkware','gifts'=>'Gifts'), 'sp_collection'=>array('attributes-of-jesus'=>'Attributes of Jesus','bible-placemats'=>'Bible placemats','pray-for-the-nations'=>'Pray for the Nations'), 'sp_product_type'=>array('tees'=>'Tees','hoodies'=>'Hoodies','placemats'=>'Placemats','tumblers'=>'Tumblers') );
foreach($terms as $tax=>$items) foreach($items as $slug=>$name) if(!term_exists($slug,$tax)) wp_insert_term($name,$tax,array('slug'=>$slug));
foreach(wc_get_products(array('status'=>'publish','limit'=>-1,'type'=>array('simple','variable'))) as $p){
 $id=$p->get_id();$name=strtolower($p->get_name());$cats=array();$types=array();$collections=array();
 if(has_term('apparel','product_cat',$id)){
  if(str_contains($name,'youth')||str_contains($name,'girls'))$cats[]='youth';else{$cats[]='women';if(!str_contains($name,'ladies'))$cats[]='men';}
  $types[]=str_contains($name,'hoodie')?'hoodies':'tees';
 }
 if(str_contains($name,'placemat')){$types[]='placemats';$collections[]='bible-placemats';$cats[]='gifts';}
 if(str_contains($name,'tumbler')){$types[]='tumblers';$cats[]='drinkware';$cats[]='gifts';}
 if(str_contains($name,'jesus')&&!str_contains($name,'disciples')&&!str_contains($name,'parables')&&!str_contains($name,'genealogy'))$collections[]='attributes-of-jesus';
 if(str_contains($name,'pray for'))$collections[]='pray-for-the-nations';
 foreach(array('product_cat'=>$cats,'sp_collection'=>$collections,'sp_product_type'=>$types) as $tax=>$slugs) if($slugs)wp_set_object_terms($id,$slugs,$tax,true);
}
foreach(array('custom'=>'Custom designs') as $slug=>$title) if(!get_page_by_path($slug))wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>''));
update_post_meta(657,'_sp_verse_reference','Revelation 1:18');update_post_meta(657,'_sp_verse_meaning','A reminder that Jesus is alive forevermore.');
flush_rewrite_rules();
WP_CLI::success('Staging merchandising prepared; original pages, prices and categories retained.');
