<?php
defined('ABSPATH')||exit;
add_action('comment_form_after_fields', 'sp_review_photo_field');add_action('comment_form_logged_in_after', 'sp_review_photo_field');
function sp_review_photo_field(){if(!is_product())return;wp_nonce_field('sp_review_photo','sp_review_photo_nonce');echo'<p class="sp-review-photo-field"><label for="sp-review-photo">Add a photo (optional)</label><input id="sp-review-photo" name="sp_review_photo" type="file" accept="image/jpeg,image/png,image/webp"><span>JPG, PNG or WebP, up to 5 MB.</span></p><p class="sp-review-photo-consent"><label><input type="checkbox" name="sp_photo_consent" value="1"> I give Seed Prints permission to display this photo with my review.</label></p>';}
add_filter('preprocess_comment',function($data){
 if('product'!==get_post_type(absint($data['comment_post_ID']??0))||empty($_FILES['sp_review_photo'])||UPLOAD_ERR_NO_FILE===($_FILES['sp_review_photo']['error']??null))return$data;
 $nonce=isset($_POST['sp_review_photo_nonce'])&&is_string($_POST['sp_review_photo_nonce'])?sanitize_text_field(wp_unslash($_POST['sp_review_photo_nonce'])):'';
 if(!wp_verify_nonce($nonce,'sp_review_photo')||'1'!==($_POST['sp_photo_consent']??''))wp_die('To attach a photo, refresh this page and confirm permission to display it.','Photo permission',array('response'=>400,'back_link'=>true));
 $rate_key='sp_photo_rate_'.substr(hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'').wp_date('Y-m-d-H'),wp_salt('nonce')),0,32);$attempts=(int)get_transient($rate_key);if($attempts>=12)wp_die('Please wait before attaching another photo.','Review photo',array('response'=>429,'back_link'=>true));set_transient($rate_key,$attempts+1,HOUR_IN_SECONDS);
 $upload=sp_save_custom_upload($_FILES['sp_review_photo']);if(is_wp_error($upload))wp_die(esc_html($upload->get_error_message()),'Review photo',array('response'=>400,'back_link'=>true));
 $base=sp_private_upload_dir();$path=$base.'/'.$upload['file'];$GLOBALS['sp_review_upload_paths']=array($path);register_shutdown_function(function(){if(empty($GLOBALS['sp_review_upload_committed']))foreach($GLOBALS['sp_review_upload_paths']??array()as$temporary)if(is_file($temporary))@unlink($temporary);});$editor=wp_get_image_editor($path);
 if(is_wp_error($editor))wp_die('The photo could not be processed. Choose another image.','Review photo',array('response'=>400,'back_link'=>true));
 $editor->resize(1000,1000,false);$editor->set_quality(80);$out=$editor->save($path.'.webp','image/webp');
 if(is_wp_error($out))wp_die('The photo could not be processed. Choose another image.','Review photo',array('response'=>400,'back_link'=>true));
 $GLOBALS['sp_review_upload_paths'][]=$out['path'];@chmod($out['path'],0600);$GLOBALS['sp_review_photo_pending']=array('original'=>$upload['file'],'public_file'=>basename($out['path']),'consent'=>true);return$data;
});
add_action('comment_post',function($id){if(!empty($GLOBALS['sp_review_photo_pending'])){update_comment_meta($id,'_sp_review_photo',$GLOBALS['sp_review_photo_pending']);$GLOBALS['sp_review_upload_committed']=true;wp_set_comment_status($id,'hold');unset($GLOBALS['sp_review_photo_pending']);wc_add_notice('Thanks for your review. Your photo will appear after it is reviewed.','notice');}},20);
add_action('admin_post_sp_review_photo','sp_serve_review_photo');add_action('admin_post_nopriv_sp_review_photo','sp_serve_review_photo');
function sp_serve_review_photo(){
 $id=isset($_GET['review'])&&is_scalar($_GET['review'])?absint($_GET['review']):0;$comment=get_comment($id);$photo=get_comment_meta($id,'_sp_review_photo',true);
 $admin=current_user_can('moderate_comments')&&isset($_GET['_wpnonce'])&&is_string($_GET['_wpnonce'])&&wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])),'sp_review_photo_'.$id);
 if(!$comment||'product'!==get_post_type($comment->comment_post_ID)||'publish'!==get_post_status($comment->comment_post_ID)||!$photo||empty($photo['consent'])||('1'!==$comment->comment_approved&&!$admin))wp_die('Photo not found.','',array('response'=>404));
 $name=$photo['public_file']??'';if(!preg_match('/^[a-f0-9]{40}\.(?:jpg|jpeg|png|webp)\.webp$/',$name))wp_die('Photo not found.','',array('response'=>404));
 $base=sp_private_upload_dir();$path=is_wp_error($base)?'':$base.'/'.$name;if(!is_file($path))wp_die('Photo not found.','',array('response'=>404));
 nocache_headers();header('Content-Type: image/webp');header('X-Content-Type-Options: nosniff');header('Content-Length: '.filesize($path));readfile($path);exit;
}
add_filter('comment_text',function($text,$comment){
 if(!$comment||'product'!==get_post_type($comment->comment_post_ID)||'1'!==$comment->comment_approved||!get_comment_meta($comment->comment_ID,'_sp_review_photo',true))return$text;
 $url=add_query_arg(array('action'=>'sp_review_photo','review'=>$comment->comment_ID),admin_url('admin-post.php'));
 return$text.'<p class="sp-review-photo"><a href="'.esc_url($url).'"><img src="'.esc_url($url).'" alt="Customer photo shared with this product review" loading="lazy" width="320" height="320"></a></p>';
},10,2);
add_filter('comment_row_actions',function($actions,$comment){if('product'===get_post_type($comment->comment_post_ID)&&get_comment_meta($comment->comment_ID,'_sp_review_photo',true)&&current_user_can('moderate_comments')){$url=wp_nonce_url(add_query_arg(array('action'=>'sp_review_photo','review'=>$comment->comment_ID),admin_url('admin-post.php')),'sp_review_photo_'.$comment->comment_ID);$actions['sp_photo']='<a href="'.esc_url($url).'">Review photo</a>';}return$actions;},10,2);
