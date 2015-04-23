<?php
// presents the bulk optimize form with the number of images, and runs it once they submit the button
function ewww_image_optimizer_bulk_preview() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_bulk_preview()</b><br>";
	// retrieve the attachment IDs that were pre-loaded in the database
	list($fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count) = ewww_image_optimizer_count_optimized ('media');
//	$upload_import = get_option('ewww_image_optimizer_imported');
	$upload_import = true;
?>
	<div class="wrap"> 
	<div id="icon-upload" class="icon32"><br /></div><h2><?php _e('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h2>
<?php	// Retrieve the value of the 'bulk resume' option and set the button text for the form to use
	$resume = get_option('ewww_image_optimizer_bulk_resume');
	if (empty($resume)) {
		$button_text = __('Start optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
	} else {
		$button_text = __('Resume previous bulk operation', EWWW_IMAGE_OPTIMIZER_DOMAIN);
	}
	$loading_image = plugins_url('/wpspin.gif', __FILE__);
	// create the html for the bulk optimize form and status divs
?>
		<div id="ewww-bulk-loading">
			<p id="ewww-loading" class="ewww-bulk-info" style="display:none"><?php _e('Importing', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>&nbsp;<img src='<?php echo $loading_image; ?>' /></p>
		</div>
		<div id="ewww-bulk-progressbar"></div>
		<div id="ewww-bulk-counter"></div>
		<form id="ewww-bulk-stop" style="display:none;" method="post" action="">
			<br /><input type="submit" class="button-secondary action" value="<?php _e('Stop Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
		</form>
		<div id="ewww-bulk-status"></div>
<?php 		if (empty($upload_import)) { ?>
			<p class="ewww-bulk-info"><?php _e('You should import Media Library images into the table to prevent duplicate optimization.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
			<form id="import-start" class="bulk-form" method="post" action="">
				<input type="submit" class="button-secondary action" value="<?php _e('Import Images', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
			</form>
		</div>
<?php			return;
		} ?>
		<form class="ewww-bulk-form">
			<p><label for="ewww-force" style="font-weight: bold"><?php _e('Force re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label>&emsp;<input type="checkbox" id="ewww-force" name="ewww-force"></p>
			<p><label for="ewww-delay" style="font-weight: bold"><?php _e('Choose how long to pause between images (in seconds, 0 = disabled)', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label>&emsp;<input type="text" id="ewww-delay" name="ewww-delay" value="<?php if ($delay = ewww_image_optimizer_get_option ( 'ewww_image_optimizer_delay' ) ) { echo $delay; } else { echo 0; } ?>"></p>
			<div id="ewww-delay-slider" style="width:50%"></div>
		</form>
		<h3><?php _e('Optimize Media Library', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h3>
<?php		if ($fullsize_count < 1) {
			echo '<p>' . __('You do not appear to have uploaded any images yet.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</p>';
		} else { ?>
			<div id="ewww-bulk-forms">
<?php			if ( ! $resize_count && ! $unoptimized_count && ! $unoptimized_resize_count) { ?>
				<p class="ewww-media-info ewww-bulk-info"><?php printf(__('%1$d images in the Media Library have been selected, unable to determine how many resizes and how many are unoptimized.', EWWW_IMAGE_OPTIMIZER_DOMAIN), $fullsize_count); ?><br />
<?php			} else { ?>
				<p class="ewww-media-info ewww-bulk-info"><?php printf(__('%1$d images in the Media Library have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count); ?><br />
<?php			} ?>
			<?php _e('Previously optimized images will be skipped by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
			<form id="ewww-bulk-start" class="ewww-bulk-form" method="post" action="">
				<input id="ewww-bulk-first" type="submit" class="button-secondary action" value="<?php echo $button_text; ?>" />
				<input id="ewww-bulk-again" type="submit" class="button-secondary action" style="display:none" value="<?php _e('Optimize Again', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
			</form>
<?php		}
		// if the 'bulk resume' option was not empty, offer to reset it so the user can start back from the beginning
		if (!empty($resume)): 
?>
			<p class="ewww-media-info ewww-bulk-info"><?php _e('If you would like to start over again, press the Reset Status button to reset the bulk operation status.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
			<form class="ewww-bulk-form" method="post" action="">
				<?php wp_nonce_field( 'ewww-image-optimizer-bulk', 'ewww_wpnonce'); ?>
				<input type="hidden" name="ewww_reset" value="1">
				<button id="ewww-bulk-reset" type="submit" class="button-secondary action"><?php _e('Reset Status', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></button>
			</form>
<?php		endif;
	echo '</div>';
	ewwwio_memory( __FUNCTION__ );
	ewww_image_optimizer_aux_images();
}

// retrieve image counts for the bulk process
function ewww_image_optimizer_count_optimized ( $gallery, $return_ids = false ) {
	global $ewww_debug;
	global $wpdb;
	$ewww_debug .= "<b>ewww_image_optimizer_count_optmized()</b><br>";
	$full_count = 0;
	$unoptimized_full = 0;
	$unoptimized_re = 0;
	$resize_count = 0;
	$attachment_query = '';
	$ewww_debug .= "scanning for $gallery<br>";
	// retrieve the time when the optimizer starts
	$started = microtime(true);
	$max_query = 3000;
	$attachment_query_count = 0;
	switch ($gallery) {
		case 'media':
			$ids = array();
			// see if we were given attachment IDs to work with via GET/POST
		        if ( ! empty($_REQUEST['ids']) || get_option('ewww_image_optimizer_bulk_resume')) {
				$ewww_debug .= 'we have preloaded attachment ids<br>';
				// retrieve the attachment IDs that were pre-loaded in the database
				$attachment_ids = get_option('ewww_image_optimizer_bulk_attachments');
				while ( $attachment_ids && $attachment_query_count < $max_query ) {
					$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
					$attachment_query_count++;
				}
				$attachment_query = 'AND metas.post_id IN (' . substr( $attachment_query, 0, -1 ) . ')';
			}
			$offset = 0;
			// retrieve all the image attachment metadata from the database
			while ( $attachments = $wpdb->get_results( "SELECT metas.meta_value,post_id FROM $wpdb->postmeta metas INNER JOIN $wpdb->posts posts ON posts.ID = metas.post_id WHERE posts.post_mime_type LIKE '%image%' AND metas.meta_key = '_wp_attachment_metadata' $attachment_query LIMIT $offset, $max_query", ARRAY_N ) ) {
				$ewww_debug .= "fetched " . count( $attachments ) . " attachments starting at $offset<br>";
				foreach ($attachments as $attachment) {
					$meta = unserialize($attachment[0]);
					if (empty($meta)) {
						continue;
					}
					if (empty($meta['ewww_image_optimizer'])) {
						$unoptimized_full++;
						$ids[] = $attachment[1];
					}
					// resized versions, so we can continue
					if (isset($meta['sizes']) ) {
						foreach($meta['sizes'] as $size => $data) {
							$resize_count++;
							if (empty($meta['sizes'][$size]['ewww_image_optimizer'])) {
								$unoptimized_re++;
							}
						}
					}
				}
				$full_count += count($attachments);
				$offset += $max_query;
				if ( ! empty( $attachment_ids ) ) {
					$attachment_query = '';
					$attachment_query_count = 0;
					$offset = 0;
					while ( $attachment_ids && $attachment_query_count < $max_query ) {
						$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
						$attachment_query_count++;
					}
					$attachment_query = 'AND metas.post_id IN (' . substr( $attachment_query, 0, -1 ) . ')';
				}
			}
			break;
		case 'ngg':
			// see if we were given attachment IDs to work with via GET/POST
		        if ( ! empty($_REQUEST['ewww_inline']) || get_option('ewww_image_optimizer_bulk_ngg_resume')) {
				// retrieve the attachment IDs that were pre-loaded in the database
				$attachment_ids = get_option('ewww_image_optimizer_bulk_ngg_attachments');
				while ( $attachment_ids && $attachment_query_count < $max_query ) {
					$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
					$attachment_query_count++;
				}
				$attachment_query = 'WHERE pid IN (' . substr( $attachment_query, 0, -1 ) . ')';
			}
			// creating the 'registry' object for working with nextgen
			$registry = C_Component_Registry::get_instance();
			// creating a database storage object from the 'registry' object
			$storage  = $registry->get_utility('I_Gallery_Storage');
			// get an array of sizes available for the $image
			$sizes = $storage->get_image_sizes();
			$offset = 0;
			while ( $attachments = $wpdb->get_col( "SELECT meta_data FROM $wpdb->nggpictures $attachment_query LIMIT $offset, $max_query" ) ) {
				foreach ($attachments as $attachment) {
					if (class_exists('Ngg_Serializable')) {
				        	$serializer = new Ngg_Serializable();
				        	$meta = $serializer->unserialize( $attachment );
					} else {
						$meta = unserialize( $attachment );
					}
					if ( ! is_array( $meta ) ) {
						continue;
					}
					if (empty($meta['ewww_image_optimizer'])) {
							$unoptimized_full++;
					}
					foreach ($sizes as $size) {
						if ($size !== 'full') {
							$resize_count++;
							if (empty($meta[$size]['ewww_image_optimizer'])) {
								$unoptimized_re++;
							}
						}
					}
				}
				$full_count += count($attachments);
				$offset += $max_query;
				if ( ! empty( $attachment_ids ) ) {
					$attachment_query = '';
					$attachment_query_count = 0;
					$offset = 0;
					while ( $attachment_ids && $attachment_query_count < $max_query ) {
						$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
						$attachment_query_count++;
					}
					$attachment_query = 'WHERE pid IN (' . substr( $attachment_query, 0, -1 ) . ')';
				}
			}
			break;
		case 'flag':
			if ( ! empty( $_REQUEST['doaction'] ) || get_option( 'ewww_image_optimizer_bulk_flag_resume' ) ) {
				// retrieve the attachment IDs that were pre-loaded in the database
				$attachment_ids = get_option('ewww_image_optimizer_bulk_flag_attachments');
				while ( $attachment_ids && $attachment_query_count < $max_query ) {
					$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
					$attachment_query_count++;
				}
				$attachment_query = 'WHERE pid IN (' . substr( $attachment_query, 0, -1 ) . ')';
			}
			$offset = 0;
			while ( $attachments = $wpdb->get_col( "SELECT meta_data FROM $wpdb->flagpictures $attachment_query LIMIT $offset, $max_query" ) ) {
				foreach ($attachments as $attachment) {
					$meta = unserialize( $attachment );
					if ( ! is_array( $meta ) ) {
						continue;
					}
					if (empty($meta['ewww_image_optimizer'])) {
						$unoptimized_full++;
					}
					if (!empty($meta['webview'])) {
						$resize_count++;
						if(empty($meta['webview']['ewww_image_optimizer'])) {
							$unoptimized_re++;
						}
					}
					if (!empty($meta['thumbnail'])) {
						$resize_count++;
						if(empty($meta['thumbnail']['ewww_image_optimizer'])) {
							$unoptimized_re++;
						}
					}
				}
				$full_count += count($attachments);
				$offset += $max_query;
				if ( ! empty( $attachment_ids ) ) {
					$attachment_query = '';
					$attachment_query_count = 0;
					$offset = 0;
					while ( $attachment_ids && $attachment_query_count < $max_query ) {
						$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
						$attachment_query_count++;
					}
					$attachment_query = 'WHERE pid IN (' . substr( $attachment_query, 0, -1 ) . ')';
				}
			}
			break;
	}
	if ( empty( $full_count ) && ! empty( $attachment_ids ) ) {
//		return array( count( $attachment_ids ), '', '', '');
		$ewww_debug .= "query appears to have failed, just counting total images instead<br>";
		$full_count = count($attachment_ids);
	}
	$elapsed = microtime(true) - $started;
	$ewww_debug .= "counting images took $elapsed seconds<br>";
	$ewww_debug .= "found $full_count fullsize ($unoptimized_full unoptimized), and $resize_count resizes ($unoptimized_re unoptimized)<br>";
	ewwwio_memory( __FUNCTION__ );
	if ( $return_ids ) {
		return $ids;
	} else {
		return array( $full_count, $unoptimized_full, $resize_count, $unoptimized_re );
	}
}

// prepares the bulk operation and includes the javascript functions
function ewww_image_optimizer_bulk_script($hook) {
	global $ewww_debug;
	// make sure we are being called from the bulk optimization page
	if ('media_page_ewww-image-optimizer-bulk' != $hook) {
		return;
	}
        // initialize the $attachments variable
        $attachments = null;
        // check to see if we are supposed to reset the bulk operation and verify we are authorized to do so
	if (!empty($_REQUEST['ewww_reset']) && wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' )) {
		// set the 'bulk resume' option to an empty string to reset the bulk operation
		update_option('ewww_image_optimizer_bulk_resume', '');
	}
        // check to see if we are supposed to reset the bulk operation and verify we are authorized to do so
	if (!empty($_REQUEST['ewww_reset_aux']) && wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-aux-images' )) {
		// set the 'bulk resume' option to an empty string to reset the bulk operation
		update_option('ewww_image_optimizer_aux_resume', '');
	}
        // check to see if we are supposed to convert the auxiliary images table and verify we are authorized to do so
	if (!empty($_REQUEST['ewww_convert']) && wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-aux-images' )) {
		ewww_image_optimizer_aux_images_convert();
	}
	// check the 'bulk resume' option
	$resume = get_option('ewww_image_optimizer_bulk_resume');
	// see if we were given attachment IDs to work with via GET/POST
        if (!empty($_REQUEST['ids'])) {
		$ids = explode(',', $_REQUEST['ids']);
		$ewww_debug .= "gallery ids: " . print_r($ids, true) . "<br>";
		$ewww_debug .= "post_type: " . get_post_type($ids[0]) . "<br>";
		if ('ims_gallery' == get_post_type($ids[0])) {
			$attachments = array();
			foreach ($ids as $gid) {
				$ewww_debug .= "gallery id: $gid<br>";
		                $ims_images = get_posts(array(
		                        'numberposts' => -1,
		                        'post_type' => 'ims_image',
					'post_status' => 'any',
		                        'post_mime_type' => 'image',
					'post_parent' => $gid,
					'fields' => 'ids'
		                ));
				$attachments = array_merge($attachments, $ims_images);
				$ewww_debug .= "attachment ids: " . print_r($attachments, true) . "<br>";
			}
		} else {
	                // retrieve post IDs correlating to the IDs submitted to make sure they are all valid
	                $attachments = get_posts( array(
	                        'numberposts' => -1,
	                        'include' => $ids,
	                        'post_type' => array('attachment', 'ims_image'),
				'post_status' => 'any',
	                        'post_mime_type' => 'image',
				'fields' => 'ids'
	                ));
		}
		// unset the 'bulk resume' option since we were given specific IDs to optimize
		update_option('ewww_image_optimizer_bulk_resume', '');
        // check if there is a previous bulk operation to resume
        } else if (!empty($resume)) {
		// retrieve the attachment IDs that have not been finished from the 'bulk attachments' option
		$attachments = get_option('ewww_image_optimizer_bulk_attachments');
	// since we aren't resuming, and weren't given a list of IDs, we will optimize everything
        } else {
                // load up all the image attachments we can find
                $attachments = get_posts( array(
                        'numberposts' => -1,
                        'post_type' => array('attachment', 'ims_image'),
			'post_status' => 'any',
                        'post_mime_type' => 'image',
			'fields' => 'ids'
                ));
        }
	// store the attachment IDs we retrieved in the 'bulk_attachments' option so we can keep track of our progress in the database
	update_option('ewww_image_optimizer_bulk_attachments', $attachments);
	wp_enqueue_script( 'ewwwbulkscript', plugins_url( '/eio.js', __FILE__ ), array( 'jquery', 'jquery-ui-slider', 'jquery-ui-progressbar' ) );
	$image_count = ewww_image_optimizer_aux_images_table_count();
	// submit a couple variables to the javascript to work with
	$attachments = json_encode($attachments);
	wp_localize_script('ewwwbulkscript', 'ewww_vars', array(
			'_wpnonce' => wp_create_nonce('ewww-image-optimizer-bulk'),
			'attachments' => $attachments,
			'image_count' => $image_count,
			'count_string' => sprintf( __( '%d images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $image_count ),
		)
	);
	// load the stylesheet for the jquery progressbar
	wp_enqueue_style('jquery-ui-progressbar', plugins_url('jquery-ui-1.10.1.custom.css', __FILE__));
	ewwwio_memory( __FUNCTION__ );
}

// find the number of images in the ewwwio_images table
function ewww_image_optimizer_aux_images_table_count() {
	global $wpdb;
	$count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->ewwwio_images");
	if (!empty($_REQUEST['ewww_inline'])) {
		echo $count;
	ewwwio_memory( __FUNCTION__ );
		die();
	}
	ewwwio_memory( __FUNCTION__ );
	return $count;
	
}

// called by javascript to initialize some output
function ewww_image_optimizer_bulk_initialize() {
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	} 
	// update the 'bulk resume' option to show that an operation is in progress
	update_option('ewww_image_optimizer_bulk_resume', 'true');
	// generate the WP spinner image for display
	$loading_image = plugins_url('/wpspin.gif', __FILE__);
	// let the user know that we are beginning
	echo "<p>" . __('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&nbsp;<img src='$loading_image' /></p>";
	ewwwio_memory( __FUNCTION__ );
	die();
}

// called by javascript to output filename of attachment in progress
function ewww_image_optimizer_bulk_filename() {
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		wp_die( __( 'Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	// get the attachment ID of the current attachment
	$attachment_ID = $_POST['ewww_attachment'];
	$meta = wp_get_attachment_metadata( $attachment_ID );
	// generate the WP spinner image for display
	$loading_image = plugins_url('/wpspin.gif', __FILE__);
	if(!empty($meta['file']))
		// let the user know the file we are currently optimizing
		echo "<p>" . __('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <b>" . $meta['file'] . "</b>&nbsp;<img src='$loading_image' /></p>";
	ewwwio_memory( __FUNCTION__ );
	die();
}
 
// called by javascript to process each image in the loop
function ewww_image_optimizer_bulk_loop() {
	global $ewww_debug;
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		wp_die( __( 'Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	} 
	if (!empty($_REQUEST['ewww_sleep'])) {
		sleep($_REQUEST['ewww_sleep']);
	}
	// retrieve the time when the optimizer starts
	$started = microtime(true);
	if ( ini_get( 'max_execution_time' ) < 60 ) {
		set_time_limit (0);
	}
	// get the attachment ID of the current attachment
	$attachment = $_POST['ewww_attachment'];
	// get the 'bulk attachments' with a list of IDs remaining
	$attachments = get_option('ewww_image_optimizer_bulk_attachments');
	$meta = wp_get_attachment_metadata( $attachment, true );
	// do the optimization for the current attachment (including resizes)
	$meta = ewww_image_optimizer_resize_from_meta_data ($meta, $attachment, false);
	if ( !empty ( $meta['file'] ) ) {
		// output the filename (and path relative to 'uploads' folder)
		printf( "<p>" . __('Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <strong>%s</strong><br>", esc_html($meta['file']) );
	} else {
		printf("<p>" . __('Skipped image, ID:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <strong>%s</strong><br>", $attachment );
	}
	if(!empty($meta['ewww_image_optimizer'])) {
		// tell the user what the results were for the original image
		printf(__('Full size – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br>", $meta['ewww_image_optimizer']);
	}
	// check to see if there are resized version of the image
	if (isset($meta['sizes']) && is_array($meta['sizes'])) {
		// cycle through each resize
		foreach ($meta['sizes'] as $size) {
			if ( ! empty( $size['ewww_image_optimizer']) ) {
				// output the results for the current resized version
				printf("%s – %s<br>", $size['file'], $size['ewww_image_optimizer']);
			}
		}
	}
	// calculate how much time has elapsed since we started
	$elapsed = microtime(true) - $started;
	// output how much time has elapsed since we started
	printf(__('Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>", $elapsed);
	// update the metadata for the current attachment
	wp_update_attachment_metadata( $attachment, $meta );
	// remove the first element from the $attachments array
	if (!empty($attachments))
		array_shift($attachments);
	// store the updated list of attachment IDs back in the 'bulk_attachments' option
	update_option('ewww_image_optimizer_bulk_attachments', $attachments);
	if ( ewww_image_optimizer_get_option ( 'ewww_image_optimizer_debug' ) ) {
		echo '<div style="background-color:#ffff99;">' . $ewww_debug . '</div>';
	}
	ewww_image_optimizer_debug_log();
	ewwwio_memory( __FUNCTION__ );
	die();
}

// called by javascript to cleanup after ourselves
function ewww_image_optimizer_bulk_cleanup() {
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	} 
	// all done, so we can update the bulk options with empty values
	update_option('ewww_image_optimizer_bulk_resume', '');
	update_option('ewww_image_optimizer_bulk_attachments', '');
	// and let the user know we are done
	echo '<p><b>' . __('Finished', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b> - <a href="upload.php">' . __('Return to Media Library', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a></p>';
	ewwwio_memory( __FUNCTION__ );
	die();
}
add_action('admin_enqueue_scripts', 'ewww_image_optimizer_bulk_script');
add_action('wp_ajax_bulk_init', 'ewww_image_optimizer_bulk_initialize');
add_action('wp_ajax_bulk_filename', 'ewww_image_optimizer_bulk_filename');
add_action('wp_ajax_bulk_loop', 'ewww_image_optimizer_bulk_loop');
add_action('wp_ajax_bulk_cleanup', 'ewww_image_optimizer_bulk_cleanup');
?>
