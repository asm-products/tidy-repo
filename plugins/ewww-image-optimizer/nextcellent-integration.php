<?php 
if ( ! class_exists('ewwwngg')) {
class ewwwngg {
	/* initializes the nextgen integration functions */
	function ewwwngg() {
		add_action('admin_init', array(&$this, 'admin_init'));
		add_filter('ngg_manage_images_columns', array(&$this, 'ewww_manage_images_columns'));
		add_action('ngg_manage_image_custom_column', array(&$this, 'ewww_manage_image_custom_column'), 10, 2);
		if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_noauto' ) ) {
			add_action('ngg_added_new_image', array(&$this, 'ewww_added_new_image'));
		}
		add_action('admin_action_ewww_ngg_manual', array(&$this, 'ewww_ngg_manual'));
		add_action('admin_menu', array(&$this, 'ewww_ngg_bulk_menu'));
//		$i18ngg = strtolower( _n( 'Gallery', 'Galleries', 1, 'nggallery' ) );
//		add_action('admin_head-' . $i18ngg . '_page_nggallery-manage-gallery', array(&$this, 'ewww_ngg_bulk_actions_script'));
		add_action('admin_head-galleries_page_nggallery-manage-gallery', array(&$this, 'ewww_ngg_bulk_actions_script'));
		add_action('admin_enqueue_scripts', array(&$this, 'ewww_ngg_bulk_script'));
		add_action('wp_ajax_bulk_ngg_preview', array(&$this, 'ewww_ngg_bulk_preview'));
		add_action('wp_ajax_bulk_ngg_init', array(&$this, 'ewww_ngg_bulk_init'));
		add_action('wp_ajax_bulk_ngg_filename', array(&$this, 'ewww_ngg_bulk_filename'));
		add_action('wp_ajax_bulk_ngg_loop', array(&$this, 'ewww_ngg_bulk_loop'));
		add_action('wp_ajax_bulk_ngg_cleanup', array(&$this, 'ewww_ngg_bulk_cleanup'));
		add_action('wp_ajax_ewww_ngg_thumbs', array(&$this, 'ewww_ngg_thumbs_only'));
		add_action('ngg_after_new_images_added', array(&$this, 'ewww_ngg_new_thumbs'), 10, 2);
	}

	function admin_init() {
		register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_ngg_resume');
		register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_ngg_attachments');
	}

	/* adds the Bulk Optimize page to the tools menu, and a hidden page for optimizing thumbnails */
	function ewww_ngg_bulk_menu () {
			add_submenu_page(NGGFOLDER, __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'NextGEN Manage gallery', 'ewww-ngg-bulk', array (&$this, 'ewww_ngg_bulk_preview'));
			$hook = add_submenu_page(null, __('Bulk Thumbnail Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), __('Bulk Thumbnail Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'NextGEN Manage gallery', 'ewww-ngg-thumb-bulk', array (&$this, 'ewww_ngg_thumb_bulk'));
	}

	/* ngg_added_new_image hook */
	function ewww_added_new_image ($image) {
		// query the filesystem path of the gallery from the database
		global $wpdb;
		$q = $wpdb->prepare( "SELECT path FROM {$wpdb->prefix}ngg_gallery WHERE gid = %d LIMIT 1", $image['galleryID'] );
		$gallery_path = $wpdb->get_var($q);
		// if we have a path to work with
		if ( $gallery_path ) {
			// construct the absolute path of the current image
			$file_path = trailingslashit($gallery_path) . $image['filename'];
			// run the optimizer on the current image
			$res = ewww_image_optimizer(ABSPATH . $file_path, 2, false, false, true);
			// update the metadata for the optimized image
			nggdb::update_image_meta($image['id'], array('ewww_image_optimizer' => $res[1]));
		}
	}

	/* output a small html form so that the user can optimize thumbs for the $images just added */
	function ewww_ngg_new_thumbs($gid, $images) {
		// store the gallery id, seems to help avoid errors
		$gallery = $gid;
		// prepare the $images array for POSTing
		$images = serialize($images); ?>
                <div id="bulk-forms"><p><?php _e('The thumbnails for your new images have not been optimized.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
                <form id="thumb-optimize" method="post" action="admin.php?page=ewww-ngg-thumb-bulk">
			<?php wp_nonce_field( 'ewww-image-optimizer-bulk', 'ewww_wpnonce'); ?>
			<input type="hidden" name="ewww_attachments" value="<?php echo $images; ?>">
                        <input type="submit" class="button-secondary action" value="<?php _e('Optimize Thumbs', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
                </form> 
<?php	}

	/* optimize the thumbs of the images POSTed from the previous page */
	function ewww_ngg_thumb_bulk() {
		$permissions = apply_filters( 'ewww_image_optimizer_manual_permissions', '' );
		if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
			wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
		}?> 
		<div class="wrap">
                <div id="icon-upload" class="icon32"></div><h2><?php _e('Bulk Thumbnail Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h2>
<?php		$images = unserialize ($_POST['ewww_attachments']);
		// initialize $current, and $started time
		$started = time();
		$current = 0;
		// find out how many images we have
		$total = sizeof($images);
		// flush the output buffers
		ob_implicit_flush(true);
		ob_end_flush();
		// process each image
		foreach ($images as $id) {
			if ( ini_get( 'max_execution_time' ) < 60 ) {
				set_time_limit (0);
			}
			$current++;
			echo "<p>" . __('Processing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " $current/$total: ";
			// get the metadata
			$meta = new nggMeta( $id );
			// output the current image name
			printf( "<strong>%s</strong>&hellip;<br>", esc_html($meta->image->filename) );
			// get the filepath of the thumbnail image
			$thumb_path = $meta->image->thumbPath;
			// run the optimization on the thumbnail
			$tres = ewww_image_optimizer($thumb_path, 2, false, true);
			// output the results of the thumb optimization
			printf(__('Thumbnail – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br>", $tres[1]);
			// outupt how much time we've spent optimizing so far
			$elapsed = time() - $started;
			printf(__('Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>", $elapsed);
			// flush the HTML output buffers
			@ob_flush();
			flush();
		}
		// all done here
		echo '<p><b>' . __('Finished', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b></p></div>';	
	}

	/* Manually process an image from the NextGEN Gallery */
	function ewww_ngg_manual() {
		// check permission of current user
		$permissions = apply_filters( 'ewww_image_optimizer_manual_permissions', '' );
		if ( FALSE === current_user_can( $permissions ) ) {
			wp_die(__('You don\'t have permission to work with uploaded files.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
		}
		// make sure function wasn't called without an attachment to work with
		if ( FALSE === isset($_GET['ewww_attachment_ID'])) {
			wp_die(__('No attachment ID was provided.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
		}
		// store the attachment $id
		$id = intval($_GET['ewww_attachment_ID']);
		// retrieve the metadata for the image
		$meta = new nggMeta( $id );
		// retrieve the image path
		$file_path = $meta->image->imagePath;
		// run the optimizer on the current image
		$res = ewww_image_optimizer($file_path, 2, false, false, true);
		// update the metadata for the optimized image
		nggdb::update_image_meta($id, array('ewww_image_optimizer' => $res[1]));
		// get the filepath of the thumbnail image
		$thumb_path = $meta->image->thumbPath;
		// run the optimization on the thumbnail
		ewww_image_optimizer($thumb_path, 2, false, true);
		// get the referring page, and send the user back there
		$sendback = wp_get_referer();
		$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);
		wp_redirect($sendback);
		exit(0);
	}

	/* ngg_manage_images_columns hook */
	function ewww_manage_images_columns( $columns ) {
		$columns['ewww_image_optimizer'] = __('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		return $columns;
	}

	/* ngg_manage_image_custom_column hook */
	function ewww_manage_image_custom_column( $column_name, $id ) {
		// once we've found our custom column
		if( $column_name == 'ewww_image_optimizer' ) {    
			// get the metadata for the image
			$meta = new nggMeta( $id );
			// get the optimization status for the image
			$status = $meta->get_META( 'ewww_image_optimizer' );
			$msg = '';
			// get the file path of the image
			$file_path = $meta->image->imagePath;
			// get the mimetype of the image
			$type = ewww_image_optimizer_mimetype($file_path, 'i');
			// retrieve the human-readable filesize of the image
			$file_size = size_format(filesize($file_path), 2);
			$file_size = str_replace('B ', 'B', $file_size);
			//$file_size = ewww_image_optimizer_format_bytes(filesize($file_path));
			$valid = true;
			// check to see if we have a tool to handle the mimetype detected
	                switch($type) {
        	                case 'image/jpeg':
					// if jpegtran is missing, tell the user
                	                if(!EWWW_IMAGE_OPTIMIZER_JPEGTRAN && !EWWW_IMAGE_OPTIMIZER_CLOUD) {
                        	                $valid = false;
	     	                                $msg = '<br>' . sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>jpegtran</em>');
	                                }
					break;
				case 'image/png':
					// if the PNG tools are missing, tell the user
					if(!EWWW_IMAGE_OPTIMIZER_PNGOUT && !EWWW_IMAGE_OPTIMIZER_OPTIPNG && !EWWW_IMAGE_OPTIMIZER_CLOUD) {
						$valid = false;
						$msg = '<br>' . sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>optipng/pngout</em>');
					}
					break;
				case 'image/gif':
					// if gifsicle is missing, tell the user
					if(!EWWW_IMAGE_OPTIMIZER_GIFSICLE && !EWWW_IMAGE_OPTIMIZER_CLOUD) {
						$valid = false;
						$msg = '<br>' . sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>gifsicle</em>');
					}
					break;
				default:
					$valid = false;
			}
			// file isn't in a format we can work with, we don't work with strangers
			if($valid == false) {
				print __('Unsupported file type', EWWW_IMAGE_OPTIMIZER_DOMAIN) . $msg;
				return;
			}
			// if we have a valid status, display it, the image size, and give a re-optimize link
			if ( ! empty ( $status ) ) {
				echo $status;
				echo "<br>" . sprintf(__('Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), $file_size);
				if ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) )  {
					printf("<br><a href=\"admin.php?action=ewww_ngg_manual&amp;ewww_force=1&amp;ewww_attachment_ID=%d\">%s</a>",
						$id,
						__('Re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN));
				}
			// otherwise, give the image size, and a link to optimize right now
			} else {
				_e('Not processed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				echo "<br>" . sprintf(__('Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), $file_size);
				if ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) )  {
					printf("<br><a href=\"admin.php?action=ewww_ngg_manual&amp;ewww_attachment_ID=%d\">%s</a>",
						$id,
						__('Optimize now!', EWWW_IMAGE_OPTIMIZER_DOMAIN));
				}
			}
		}
	}

	/* output the html for the bulk optimize page */
	function ewww_ngg_bulk_preview() {
		global $ewww_debug;
		if (!empty($_POST['doaction'])) {
                        // if there is no requested bulk action, do nothing
                        if (empty($_REQUEST['bulkaction'])) {
                                return;
                        }
                        // if there is no media to optimize, do nothing
                        if (empty($_REQUEST['doaction']) || !is_array($_REQUEST['doaction'])) {
                              return;
                        }
                }
		// retrieve the attachments array from the db
                $attachments = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		// make sure there are some attachments to process
                if (count($attachments) < 1) {
                        echo '<p>' . __('You do not appear to have uploaded any images yet.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</p>';
                        return;
                }
                ?>
		<div class="wrap">
                <div id="icon-upload" class="icon32"></div><h2><?php _e('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h2>
                <?php
                // Retrieve the value of the 'bulk resume' option and set the button text for the form to use
                $resume = get_option('ewww_image_optimizer_bulk_ngg_resume');
                if (empty($resume)) {
                        $button_text = __('Start optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
                } else {
                        $button_text = __('Resume previous bulk operation', EWWW_IMAGE_OPTIMIZER_DOMAIN);
                }
                ?>
                <div id="ewww-bulk-loading"></div>
                <div id="ewww-bulk-progressbar"></div>
                <div id="ewww-bulk-counter"></div>
		<form id="ewww-bulk-stop" style="display:none;" method="post" action="">
			<br /><input type="submit" class="button-secondary action" value="<?php _e('Stop Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
		</form>
                <div id="ewww-bulk-status"></div>
                <div id="ewww-bulk-forms">
                <p class="ewww-bulk-info"><?php printf(__('We have %d images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN), count($attachments)); ?><br />
		<?php _e('Previously optimized images will be skipped by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
                <form id="ewww-bulk-start" class="ewww-bulk-form" method="post" action="">
			<input type="hidden" id="ewww-delay" name="ewww-delay" value="0">
                        <input type="submit" class="button-secondary action" value="<?php echo $button_text; ?>" />
                </form>
                <?php
		// if there is a previous bulk operation to resume, give the user the option to reset the resume flag
                if (!empty($resume)) { ?>
                        <p class="ewww-bulk-info"><?php _e('If you would like to start over again, press the Reset Status button to reset the bulk operation status.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
                        <form id="ewww-bulk-reset" class="ewww-bulk-form" method="post" action="">
                                <?php wp_nonce_field( 'ewww-image-optimizer-bulk-reset', 'ewww_wpnonce'); ?>
                                <input type="hidden" name="ewww_reset" value="1">
                                <input type="submit" class="button-secondary action" value="<?php _e('Reset Status', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
                        </form>
<?php           }
	        echo '</div></div>';
		if ( ewww_image_optimizer_get_option ( 'ewww_image_optimizer_debug' ) ) {
			echo '<div style="background-color:#ffff99;">' . $ewww_debug . '</div>';
		}
		if (!empty($_REQUEST['ewww_inline'])) {
			die();
		}
		return;
	}

	/* prepares the javascript for a bulk operation */
	function ewww_ngg_bulk_script($hook) {
		global $ewww_debug;
		$ewww_debug .= "<b>ewww_ngg_bulk_script</b><br />";
//		$i18ngg = strtolower ( _n( 'Gallery', 'Galleries', 1, 'nggallery' ) );
		$i18ngg = strtolower ( __( 'Galleries', 'nggallery' ) );
		$ewww_debug .= "i18n string for galleries: $i18ngg <br>";
		// make sure we are on a legitimate page and that we have the proper POST variables if necessary
		if ($hook != $i18ngg . '_page_ewww-ngg-bulk' && $hook != $i18ngg . '_page_nggallery-manage-gallery')
				return;
		if ($hook == $i18ngg . '_page_nggallery-manage-gallery' && (empty($_REQUEST['bulkaction']) || $_REQUEST['bulkaction'] != 'bulk_optimize'))
				return;
		if ($hook == $i18ngg . '_page_nggallery-manage-gallery' && (empty($_REQUEST['doaction']) || !is_array($_REQUEST['doaction'])))
				return;
		$images = null;
		// see if the user wants to reset the previous bulk status
		if (!empty($_REQUEST['ewww_reset']) && wp_verify_nonce($_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk-reset'))
			update_option('ewww_image_optimizer_bulk_ngg_resume', '');
		// see if there is a previous operation to resume
		$resume = get_option('ewww_image_optimizer_bulk_ngg_resume');
		// if we've been given a bulk action to perform
		if (!empty($_REQUEST['doaction'])) {
			// if we are optimizing a specific group of images
			if ($_REQUEST['page'] == 'manage-images' && $_REQUEST['bulkaction'] == 'bulk_optimize') {
				$ewww_debug .= "optimizing a group of images<br />";
				check_admin_referer('ngg_updategallery');
				// reset the resume status, not allowed here
				update_option('ewww_image_optimizer_bulk_ngg_resume', '');
				// retrieve the image IDs from POST
				$images = array_map( 'intval', $_REQUEST['doaction']);
			}
			// if we are optimizing a specific group of galleries
			if ($_REQUEST['page'] == 'manage-galleries' && $_REQUEST['bulkaction'] == 'bulk_optimize') {
				$ewww_debug .= "optimizing a group of galleries<br />";
				check_admin_referer('ngg_bulkgallery');
				global $nggdb;
				// reset the resume status, not allowed here
				update_option('ewww_image_optimizer_bulk_ngg_resume', '');
				$ids = array();
				// for each gallery we are given
				foreach ($_REQUEST['doaction'] as $gid) {
					// get a list of IDs
					$gallery_list = $nggdb->get_gallery($gid);
					// for each ID
					foreach ($gallery_list as $image) {
						// add it to the array
						$images[] = $image->pid;
					}
				}
			}
		// otherwise, if we have an operation to resume
		} elseif (!empty($resume)) {
			$ewww_debug .= "resuming a previous operation (maybe)<br />";
			// get the list of attachment IDs from the db
			$images = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		// otherwise, if we are on the standard bulk page, get all the images in the db
		} elseif ($hook == $i18ngg . '_page_ewww-ngg-bulk') {
			$ewww_debug .= "starting from scratch, grabbing all the images<br />";
			global $wpdb;
			$images = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures ORDER BY sortorder ASC");
		} else {
			$ewww_debug .= "$hook <br>";
		}
		
		// store the image IDs to process in the db
		update_option('ewww_image_optimizer_bulk_ngg_attachments', $images);
		// add the EWWW IO script
		wp_enqueue_script('ewwwbulkscript', plugins_url('/eio.js', __FILE__), array('jquery', 'jquery-ui-progressbar', 'jquery-ui-slider'));
		// replacing the built-in nextgen styling rules for progressbar
		wp_register_style( 'ngg-jqueryui', plugins_url('jquery-ui-1.10.1.custom.css', __FILE__));
		// enqueue the progressbar styling
		wp_enqueue_style('ngg-jqueryui'); //, plugins_url('jquery-ui-1.10.1.custom.css', __FILE__));
		// prep the $images for use by javascript
		$images = json_encode($images);
		// include all the vars we need for javascript
		wp_localize_script('ewwwbulkscript', 'ewww_vars', array(
				'_wpnonce' => wp_create_nonce('ewww-image-optimizer-bulk'),
				'gallery' => 'nextgen',
				'attachments' => $images
			)
		);
	}

	/* start the bulk operation */
	function ewww_ngg_bulk_init() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
                if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
                        wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
                }
		// toggle the resume flag to indicate an operation is in progress
                update_option('ewww_image_optimizer_bulk_ngg_resume', 'true');
		// let the user know we are starting
                $loading_image = plugins_url('/wpspin.gif', __FILE__);
                echo "<p>" . __('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&nbsp;<img src='$loading_image' alt='loading'/></p>";
                die();
        }

	/* output the filename of the image being optimized */
	function ewww_ngg_bulk_filename() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
                if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
                        wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
                }
		// need this file to work with metadata
		require_once(WP_CONTENT_DIR . '/plugins/nextcellent-gallery-nextgen-legacy/lib/meta.php');
		$id = $_POST['attachment'];
		// get the meta for the image
		$meta = new nggMeta($id);
		$loading_image = plugins_url('/wpspin.gif', __FILE__);
		// get the filename for the image, and output our current status
		$file_name = esc_html($meta->image->filename);
		echo "<p>" . __('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <b>" . $file_name . "</b>&nbsp;<img src='$loading_image' alt='loading'/></p>";
		die();
	}

	/* process each image in the bulk loop */
	function ewww_ngg_bulk_loop() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
                if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
                        wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
                }
		// need this file to work with metadata
		require_once(WP_CONTENT_DIR . '/plugins/nextcellent-gallery-nextgen-legacy/lib/meta.php');
		// find out what time we started, in microseconds
		$started = microtime(true);
		$id = $_POST['ewww_attachment'];
		// get the metadata
		$meta = new nggMeta($id);
		// retrieve the filepath
		$file_path = $meta->image->imagePath;
		// run the optimizer on the current image
		$fres = ewww_image_optimizer($file_path, 2, false, false, true);
		// update the metadata of the optimized image
		nggdb::update_image_meta($id, array('ewww_image_optimizer' => $fres[1]));
		// output the results of the optimization
		printf("<p>" . __('Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <strong>%s</strong><br>", $meta->image->filename);
		printf(__('Full size - %s', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br>", $fres[1]);
		// get the filepath of the thumbnail image
		$thumb_path = $meta->image->thumbPath;
		// run the optimization on the thumbnail
		$tres = ewww_image_optimizer($thumb_path, 2, false, true);
		// output the results of the thumb optimization
		printf(__('Thumbnail - %s', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br>", $tres[1]);
		// outupt how much time we spent
		$elapsed = microtime(true) - $started;
		printf(__('Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>", $elapsed);
		// get the list of attachments remaining from the db
		$attachments = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		// remove the first item
		if (!empty($attachments))
			array_shift($attachments);
		// and store the list back in the db
		update_option('ewww_image_optimizer_bulk_ngg_attachments', $attachments);
		die();
	}

	/* finish the bulk operation */
	function ewww_ngg_bulk_cleanup() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
                if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
                        wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
                }
		// reset all the bulk options in the db
		update_option('ewww_image_optimizer_bulk_ngg_resume', '');
		update_option('ewww_image_optimizer_bulk_ngg_attachments', '');
		// and let the user know we are done
		echo '<p><b>' . __('Finished Optimization!', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b></p>';
		die();
	}

	// insert a bulk optimize option in the actions list for the gallery and image management pages (via javascript, since we have no hooks)
	function ewww_ngg_bulk_actions_script() {
		if ( ! current_user_can( apply_filters( 'ewww_image_optimizer_bulk_permissions', '' ) ) ) {
			return;
		}
?>		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('select[name^="bulkaction"] option:last-child').after('<option value="bulk_optimize">Bulk Optimize</option>');
			});
		</script>
<?php	}
}
// initialize the plugin and the class
//add_action('init', 'ewwwngg');
//add_action('admin_print_scripts-tools_page_ewww-ngg-bulk', 'ewww_image_optimizer_scripts');

//function ewwwngg() {
	global $ewwwngg;
	$ewwwngg = new ewwwngg();
//}
}
