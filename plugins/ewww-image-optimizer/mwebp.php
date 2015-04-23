<?php
function ewww_image_optimizer_webp_migrate_preview() {
	global $ewww_debug;
?>	<div class="wrap"> 
	<div id="icon-upload" class="icon32"><br /></div><h2><?php _e('Migrate WebP Images', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h2>
<?php		_e( 'The migration is split into two parts. First, the plugin needs to scan all folders for webp images. Once it has obtained the list of images to rename, it will proceed with the renaming' );
	$button_text = __('Start Migration', EWWW_IMAGE_OPTIMIZER_DOMAIN);
	$loading_image = plugins_url('/wpspin.gif', __FILE__);
	// create the html for the migration form and status divs
?>
		<div id="webp-loading">
		</div>
		<div id="webp-progressbar"></div>
		<div id="webp-counter"></div>
		<div id="webp-status"></div>
			<div id="bulk-forms">
			<form id="webp-start" class="webp-form" method="post" action="">
				<input id="webp-first" type="submit" class="button-secondary action" value="<?php echo $button_text; ?>" />
			</form>
	</div>
<?php		
}

// scan a folder for webp images using the old naming scheme and return them as an array 
function ewww_image_optimizer_webp_scan() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_webp_scan()</b><br>";
	$list = Array();
	$dir = get_home_path();
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::CHILD_FIRST);
	$start = microtime(true);
	$file_counter = 0;
	foreach ($iterator as $path) {
		set_time_limit (0);
		$skip_optimized = false;
		if ($path->isDir()) {
			continue;
		} else {
			$file_counter++;
			$path = $path->getPathname();
			$newwebpformat = preg_replace('/\.webp/', '', $path);
			if ( file_exists( $newwebpformat ) ) {
				continue;
			}
			if ( preg_match( '/\.webp$/', $path ) ) {
				$ewww_debug .= "queued $path<br>";
				$list[] = $path;
			}
		}
	}
	$end = microtime(true) - $start;
        $ewww_debug .= "query time for $file_counter files (seconds): $end <br>";
	return $list;
}

// prepares the migration and includes the javascript functions
function ewww_image_optimizer_webp_script($hook) {
	global $ewww_debug;
	// make sure we are being called from the migration page
	if ('admin_page_ewww-image-optimizer-webp-migrate' != $hook) {
		return;
	}
//	$ewww_debug .= "starting memory usage: " . memory_get_usage(true) . "<br>";
	$images = ewww_image_optimizer_webp_scan(); 
	// remove the images array from the db if it currently exists, and then store the new list in the database
	if ( get_option ( 'ewww_image_optimizer_webp_images' ) ) {
		delete_option('ewww_image_optimizer_webp_images');
	}	
	add_option('ewww_image_optimizer_webp_images', '', '', 'no');
	update_option('ewww_image_optimizer_webp_images', $images);
	wp_enqueue_script('ewwwwebpscript', plugins_url('/webp.js', __FILE__), array('jquery'));
	$image_count = count($images);
	// submit a couple variables to the javascript to work with
	wp_localize_script('ewwwwebpscript', 'ewww_vars', array(
			'ewww_wpnonce' => wp_create_nonce('ewww-image-optimizer-webp'),
		)
	);
}

// called by javascript to initialize some output
function ewww_image_optimizer_webp_initialize() {
	// verify that an authorized user has started the migration
	$permissions = apply_filters( 'ewww_image_optimizer_admin_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-webp' ) || ! current_user_can( $permissions ) ) {
		wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	}
	if ( get_option( 'ewww_image_optimizer_webp_skipped' ) ) {
		delete_option( 'ewww_image_optimizer_webp_skipped' );
	}
	add_option( 'ewww_image_optimizer_webp_skipped', '', '', 'no' );
	// generate the WP spinner image for display
	$loading_image = plugins_url('/wpspin.gif', __FILE__);
	// let the user know that we are beginning
	echo "<p>" . __('Scanning', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&nbsp;<img src='$loading_image' /></p>";
	die();
}

// called by javascript to process each image in the loop
function ewww_image_optimizer_webp_loop() {
	global $ewww_debug;
	$permissions = apply_filters( 'ewww_image_optimizer_admin_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-webp' ) || ! current_user_can( $permissions ) ) {
		wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	} 
	// retrieve the time when the migration starts
	$started = microtime(true);
	// allow 50 seconds for each loop
	set_time_limit (50);
	$images = array();
	$ewww_debug .= 'renaming images now<br>';
	$images_processed = 0;
	$images_skipped = '';
	$images = get_option('ewww_image_optimizer_webp_images');
	if ($images) {
		printf( __( '%d Webp images left to rename.', EWWW_IMAGE_OPTIMIZER_DOMAIN), count( $images ) );
		echo "<br>";
	}
	while ($images) {
		$images_processed++;
		$ewww_debug .= "processed $images_processed images so far<br>";
		if ( $images_processed > 1000 ) {
			$ewww_debug .= "hit 1000, breaking loop";
			break;
		}
		$image = array_pop( $images );
		$replace_base = '';
		$skip = true;
		$pngfile = preg_replace('/webp$/', 'png', $image);
		$PNGfile = preg_replace('/webp$/', 'PNG', $image);
		$jpgfile = preg_replace('/webp$/', 'jpg', $image);
		$jpegfile = preg_replace('/webp$/', 'jpeg', $image);
		$JPGfile = preg_replace('/webp$/', 'JPG', $image);
		if ( file_exists( $pngfile ) ) {
			$replace_base = $pngfile;
			$skip = false;
		} if ( file_exists( $PNGfile ) ) {
			if ( empty( $replace_base ) ) {
				$replace_base = $PNGfile;
				$skip = false;
			} else {
				$skip = true;
			}
		} if ( file_exists( $jpgfile ) ) {
			if ( empty( $replace_base ) ) {
				$replace_base = $jpgfile;
				$skip = false;
			} else {
				$skip = true;
			}
		} if ( file_exists( $jpegfile ) ) {
			if ( empty( $replace_base ) ) {
				$replace_base = $jpegfile;
				$skip = false;
			} else {
				$skip = true;
			}
		} if ( file_exists( $JPGfile ) ) {
			if ( empty( $replace_base ) ) {
				$replace_base = $JPGfile;
				$skip = false;
			} else {
				$skip = true;
			}
		} 
		if ($skip) {
			if ($replace_base) {
				$ewww_debug .= "multiple replacement options for $image, not renaming<br>";
			} else {
				$ewww_debug .= "no match found for $image, strange...<br>";
			}
			$images_skipped .= "$image<br>";
		} else {
			$ewww_debug .= "renaming $image with match of $replace_base<br>";
			rename( $image, $replace_base . '.webp' );
		}
	}
	if ( $images_skipped ) {
		update_option( 'ewww_image_optimizer_webp_skipped', get_option( 'ewww_image_optimizer_webp_skipped' ) . $images_skipped );
	}
	// calculate how much time has elapsed since we started
	$elapsed = microtime(true) - $started;
	$ewww_debug .= "took $elapsed seconds this time around<br>";
	// store the updated list of images back in the database
	update_option( 'ewww_image_optimizer_webp_images', $images );
//	$ewww_debug .= "peak memory usage: " . memory_get_peak_usage(true) . "<br>";
	ewww_image_optimizer_debug_log();
	die();
}

// called by javascript to cleanup after ourselves
function ewww_image_optimizer_webp_cleanup() {
	$permissions = apply_filters( 'ewww_image_optimizer_admin_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-webp' ) || ! current_user_can( $permissions ) ) {
		wp_die( __( 'Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	$skipped = get_option( 'ewww_image_optimizer_webp_skipped' );
	// all done, so we can remove the webp options
	delete_option( 'ewww_image_optimizer_webp_images' );
	delete_option( 'ewww_image_optimizer_webp_skipped', '' );
	if ( $skipped ) {
		echo '<p><b>' . __( 'Skipped:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</b></p>';
		echo "<p>$skipped</p>";
	}
	// and let the user know we are done
	echo '<p><b>' . __( 'Finished', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</b></p>';
	die();
}
add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_webp_script' );
add_action( 'wp_ajax_webp_init', 'ewww_image_optimizer_webp_initialize' );
add_action( 'wp_ajax_webp_loop', 'ewww_image_optimizer_webp_loop' );
add_action( 'wp_ajax_webp_cleanup', 'ewww_image_optimizer_webp_cleanup' );
?>
