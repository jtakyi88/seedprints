<?php
defined('ABSPATH')||exit;
function sp_size_table($caption,$headers,$rows){echo '<div class="sp-size-table" role="region" aria-label="'.esc_attr($caption).'" tabindex="0"><table><caption>'.esc_html($caption).' — inches</caption><thead><tr><th scope="col">Measurement</th>';foreach($headers as$h)echo'<th scope="col">'.esc_html($h).'</th>';echo'</tr></thead><tbody>';foreach($rows as$label=>$cells){echo'<tr><th scope="row">'.esc_html($label).'</th>';foreach($cells as$cell)echo'<td>'.esc_html($cell).'</td>';echo'</tr>';}echo'</tbody></table></div>';}
function sp_size_guide_text(){
 sp_size_table('Adult',array('XS','S','M','L','XL','2XL','3XL','4XL'),array('Body width'=>array('17','19','20½','22','24','26','28','30'),'Body length'=>array('27','28','29','30','31','32','33','34')));
 sp_size_table('V-neck',array('XS','S','M','L','XL','2XL','3XL'),array('Body length'=>array('25½','26','26½','27','27½','28','29')));
 sp_size_table('Youth',array('XS','S','M','L','XL'),array('US size'=>array('4','6–8','10–12','14–16','18–20'),'Sleeve length'=>array('13¼','14¼','15¼','16¼','17¼'),'Body length'=>array('20½','22','23½','25','26½'),'Body width'=>array('16','17','18','19','20')));
 echo'<p>Measurements are from the existing Seed Prints size chart. Sizes available depend on the product.</p>';
}
