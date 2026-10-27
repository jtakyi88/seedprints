<?php
if('staging'!==wp_get_environment_type())WP_CLI::error('Staging only');
$GLOBALS['sp_test_count']=0;function check_sp($condition,$message){if(!$condition)WP_CLI::error($message);$GLOBALS['sp_test_count']++;}
check_sp(sp_lookup_verse('John 3:16')['reference']==='John 3:16','John lookup');check_sp(str_contains(sp_lookup_verse('Psalm 119:105')['text'],'lamp unto my feet'),'Psalm lookup');foreach(array('John 999:1','John 3:1-20','../../wp-config 1:1','John 3:0','John 3:16-1')as$r)check_sp(is_wp_error(sp_lookup_verse($r)),'Reject invalid verse');
$p=wc_get_product(12999);$v=null;foreach($p->get_available_variations()as$c)if($c['is_in_stock']&&$c['is_purchasable']){$v=$c;break;}
check_sp((bool)$v,'Available custom variation');$base=array_merge(array('product_id'=>12999,'variation_id'=>$v['variation_id'],'design_text'=>'Test design','verse_reference'=>'John 3:16','font'=>'Inter','color'=>'#1f1b16','quantity'=>'1','needed_by'=>wp_date('Y-m-d',time()+DAY_IN_SECONDS),'design_final'=>'1','email'=>'','submit_type'=>'cart'),$v['attributes']);
check_sp(!is_wp_error(sp_validate_custom_data($base)),'Valid custom design');foreach(array('design_final'=>'0','quantity'=>'0','color'=>'javascript:alert(1)','font'=>'Unknown','needed_by'=>'2000-01-01','design_text'=>str_repeat('x',1201),'variation_id'=>12178,'product_id'=>657,'submit_type'=>'invalid')as$k=>$value){$bad=$base;$bad[$k]=$value;check_sp(is_wp_error(sp_validate_custom_data($bad)),'Reject invalid '.$k);}
foreach(array_keys($v['attributes'])as$key){$bad=$base;$bad[$key]='not-an-option';check_sp(is_wp_error(sp_validate_custom_data($bad)),'Reject forged option');}
check_sp(sp_custom_fee(12999)===7.5,'Preserve tumbler customization charge');check_sp(sp_custom_fee(12689)===6.5,'Preserve shirt customization charge');
check_sp(!sp_custom_product_allowed(13196),'Standard tumbler remains ordinary');check_sp(sp_custom_product_allowed(12376),'Custom speaker tumbler allowed');
echo wp_json_encode(array('assertions'=>$GLOBALS['sp_test_count'],'passed'=>true,'writes'=>false))."\n";
