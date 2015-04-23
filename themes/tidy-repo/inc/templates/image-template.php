<img<?php
if(is_feed()) {
	echo ' src="';
	if($view->image_attachment_data['medium']) {
		echo $view->image_attachment_data['medium']['url'];
	} else {
		echo $view->image_attachment_data['full']['url'];
	}
	echo '"';
} else {
	echo $view->get_image_attribute_string();
	if(false !== $view->image_attributes['attachment_id']){
	  echo $view->get_sizes();
	  echo ' srcset="' . $view->format_srcset($template_data) . '"';
	}
 }
  ?> />