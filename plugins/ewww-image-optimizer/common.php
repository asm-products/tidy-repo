<?php
// common functions for Standard and Cloud plugins

define( 'EWWW_IMAGE_OPTIMIZER_VERSION', '232.0' );

// initialize debug global
$disabled = ini_get( 'disable_functions' );
if ( preg_match( '/get_current_user/', $disabled ) ) {
	$ewww_debug = '';
} else {
	$ewww_debug = get_current_user() . '<br>';
}

$ewww_debug .= 'EWWW IO version: ' . EWWW_IMAGE_OPTIMIZER_VERSION . '<br>';

// check the WP version (mostly for debugging purposes)
global $wp_version;
$my_version = substr( $wp_version, 0, 3 );
$ewww_debug .= "WP version: $wp_version<br>";

if ( WP_DEBUG ) {
	$ewww_memory = 'plugin load: ' . memory_get_usage( true ) . "\n";
}

// setup custom $wpdb attribute for our image-tracking table
global $wpdb;
if ( ! isset( $wpdb->ewwwio_images ) ) {
	$wpdb->ewwwio_images = $wpdb->prefix . "ewwwio_images";
}

/*add_action( 'contextual_help', 'wptuts_screen_help', 10, 3 );
function wptuts_screen_help( $contextual_help, $screen_id, $screen ) {
 
    // The add_help_tab function for screen was introduced in WordPress 3.3.
    if ( ! method_exists( $screen, 'add_help_tab' ) )
        return $contextual_help;
 
    global $hook_suffix;
 
    // List screen properties
    $variables = '<ul style="width:50%;float:left;"> <strong>Screen variables </strong>'
        . sprintf( '<li> Screen id : %s</li>', $screen_id )
        . sprintf( '<li> Screen base : %s</li>', $screen->base )
        . sprintf( '<li>Parent base : %s</li>', $screen->parent_base )
        . sprintf( '<li> Parent file : %s</li>', $screen->parent_file )
        . sprintf( '<li> Hook suffix : %s</li>', $hook_suffix )
        . '</ul>';
 
    // Append global $hook_suffix to the hook stems
    $hooks = array(
        "load-$hook_suffix",
        "admin_print_styles-$hook_suffix",
        "admin_print_scripts-$hook_suffix",
        "admin_head-$hook_suffix",
        "admin_footer-$hook_suffix"
    );
 
    // If add_meta_boxes or add_meta_boxes_{screen_id} is used, list these too
    if ( did_action( 'add_meta_boxes_' . $screen_id ) )
        $hooks[] = 'add_meta_boxes_' . $screen_id;
 
    if ( did_action( 'add_meta_boxes' ) )
        $hooks[] = 'add_meta_boxes';
 
    // Get List HTML for the hooks
    $hooks = '<ul style="width:50%;float:left;"> <strong>Hooks </strong> <li>' . implode( '</li><li>', $hooks ) . '</li></ul>';
 
    // Combine $variables list with $hooks list.
    $help_content = $variables . $hooks;
 
    // Add help panel
    $screen->add_help_tab( array(
        'id'      => 'wptuts-screen-help',
        'title'   => 'Screen Information',
        'content' => $help_content,
    ));
 
    return $contextual_help;
}*/

/**
 * Hooks
 */
if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_noauto' ) ) {
	add_filter( 'wp_handle_upload', 'ewww_image_optimizer_handle_upload' );
	add_action( 'add_attachment', 'ewww_image_optimizer_add_attachment' );
	add_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	add_filter( 'wp_generate_attachment_metadata', 'ewww_image_optimizer_resize_from_meta_data', 15, 2 );
}

// this hook is used to ensure we populate the metadata with webp images
add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment_metadata', 8, 2 );
add_filter( 'manage_media_columns', 'ewww_image_optimizer_columns' );
// filters to set default permissions, anyone can override these if they wish
add_filter( 'ewww_image_optimizer_manual_permissions', 'ewww_image_optimizer_manual_permissions', 8 );
add_filter( 'ewww_image_optimizer_bulk_permissions', 'ewww_image_optimizer_admin_permissions', 8 );
add_filter( 'ewww_image_optimizer_admin_permissions', 'ewww_image_optimizer_admin_permissions', 8 );
add_filter( 'ewww_image_optimizer_superadmin_permissions', 'ewww_image_optimizer_superadmin_permissions', 8 );
// variable for plugin settings link
$plugin = plugin_basename ( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE );
add_filter( "plugin_action_links_$plugin", 'ewww_image_optimizer_settings_link' );
add_filter( 'ewww_image_optimizer_settings', 'ewww_image_optimizer_filter_settings_page' );
add_filter( 'myarcade_filter_screenshot', 'ewww_image_optimizer_myarcade_thumbnail' );
add_filter( 'myarcade_filter_thumbnail', 'ewww_image_optimizer_myarcade_thumbnail' );
add_action( 'manage_media_custom_column', 'ewww_image_optimizer_custom_column', 10, 2 );
add_action( 'plugins_loaded', 'ewww_image_optimizer_preinit' );
add_action( 'admin_init', 'ewww_image_optimizer_admin_init' );
add_action( 'admin_action_ewww_image_optimizer_manual_optimize', 'ewww_image_optimizer_manual' );
add_action( 'admin_action_ewww_image_optimizer_manual_restore', 'ewww_image_optimizer_manual' );
add_action( 'admin_action_ewww_image_optimizer_manual_convert', 'ewww_image_optimizer_manual' );
add_action( 'delete_attachment', 'ewww_image_optimizer_delete' );
add_action( 'admin_menu', 'ewww_image_optimizer_admin_menu', 60 );
add_action( 'network_admin_menu', 'ewww_image_optimizer_network_admin_menu' );
add_action( 'load-upload.php', 'ewww_image_optimizer_load_admin_js' );
add_action( 'admin_action_bulk_optimize', 'ewww_image_optimizer_bulk_action_handler' ); 
add_action( 'admin_action_-1', 'ewww_image_optimizer_bulk_action_handler' ); 
add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_media_scripts' );
add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_settings_script' );
add_action( 'ewww_image_optimizer_auto', 'ewww_image_optimizer_auto' );
add_action( 'wr2x_retina_file_added', 'ewww_image_optimizer_retina', 20, 2 );
register_deactivation_hook( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE, 'ewww_image_optimizer_network_deactivate' );
//add_action( 'shutdown', 'ewwwio_memory_output' );
if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp_for_cdn' ) ) {
	add_action( 'init', 'ewww_image_optimizer_buffer_start' );
	add_action( 'wp_enqueue_scripts', 'ewww_image_optimizer_webp_load_jquery' );
	add_action( 'wp_print_footer_scripts', 'ewww_image_optimizer_webp_inline_script' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'iocli.php' );
}

function ewww_image_optimizer_get_plugin_version( $plugin_file ) {
	global $ewww_debug;
	$ewww_debug .= '<b>ewww_image_optimizer_get_plugin_version()</b><br>';
        $default_headers = array(
		'Version' => 'Version',
	);
	$plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );
	return $plugin_data;
}

// functions to capture all page output, replace image urls with webp derivatives, and add webp fallback 
function ewww_image_optimizer_buffer_start() {
	global $ewww_debug;
	$ewww_debug .= '<b>ewww_image_optimizer_buffer_start()</b><br>';
	ob_start( 'ewww_image_optimizer_filter_page_output' );
}
function ewww_image_optimizer_buffer_end() {
	global $ewww_debug;
	$ewww_debug .= '<b>ewww_image_optimizer_buffer_end()</b><br>';
	ob_end_flush();
}
function ewww_image_optimizer_filter_page_output( $buffer ) {
	global $ewww_debug;
	$ewww_debug .= '<b>ewww_image_optimizer_filter_page_output()</b><br>';
	if ( empty ( $buffer ) || is_admin() ) {
		return $buffer;
	}
	// modify buffer here, and then return the updated code
	if ( class_exists( 'DOMDocument' ) ) {
		preg_match( '/.+<head>/s', $buffer, $html_head );
		if ( empty( $html_head ) ) {
			return $buffer;
		}
		$html = new DOMDocument;
		$libxml_previous_error_reporting = libxml_use_internal_errors( true );
		$html->encoding = 'utf-8';
//		$html->loadHTML(utf8_decode($buffer));
		$html->loadHTML( $buffer );
		$images = $html->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			if ( $image->parentNode->tagName == 'noscript' ) {
				continue;
			}
			$home_url = get_home_url();
			$file = $image->getAttribute( 'src' );
			$filepath = ABSPATH . str_replace( $home_url, '', $file );
			if ( file_exists( $filepath . '.webp' ) ) {
				$nscript = $html->createElement( 'noscript' );
				$nscript->setAttribute( 'data-img', $file );
				$nscript->setAttribute( 'data-webp', $file . '.webp' );
				if ( $image->getAttribute( 'align' ) )
					$nscript->setAttribute( 'data-align', $image->getAttribute( 'align' ) );
				if ( $image->getAttribute( 'alt' ) )
					$nscript->setAttribute( 'data-alt', $image->getAttribute( 'alt' ) );
				if ( $image->getAttribute( 'border' ) )
					$nscript->setAttribute('data-border', $image->getAttribute( 'border' ) );
				if ( $image->getAttribute( 'crossorigin' ) )
					$nscript->setAttribute( 'data-crossorigin', $image->getAttribute( 'crossorigin' ) );
				if ( $image->getAttribute( 'height' ) )
					$nscript->setAttribute( 'data-height', $image->getAttribute( 'height' ) );
				if ( $image->getAttribute( 'hspace' ) )
					$nscript->setAttribute( 'data-hspace', $image->getAttribute( 'hspace' ) );
				if ( $image->getAttribute( 'ismap' ) )
					$nscript->setAttribute( 'data-ismap', $image->getAttribute( 'ismap' ) );
				if ( $image->getAttribute( 'longdesc' ) )
					$nscript->setAttribute( 'data-longdesc', $image->getAttribute( 'longdesc' ) );
				if ( $image->getAttribute( 'usemap' ) )
					$nscript->setAttribute( 'data-usemap', $image->getAttribute( 'usemap' ) );
				if ( $image->getAttribute( 'vspace' ) )
					$nscript->setAttribute( 'data-vspace', $image->getAttribute( 'vspace' ) );
				if ( $image->getAttribute('width') )
					$nscript->setAttribute('data-width', $image->getAttribute('width'));
				if ( $image->getAttribute('accesskey') )
					$nscript->setAttribute('data-accesskey', $image->getAttribute('accesskey'));
				if ( $image->getAttribute('class') )
					$nscript->setAttribute('data-class', $image->getAttribute('class'));
				if ( $image->getAttribute('contenteditable') )
					$nscript->setAttribute('data-contenteditable', $image->getAttribute('contenteditable'));
				if ( $image->getAttribute('contextmenu') )
					$nscript->setAttribute('data-contextmenu', $image->getAttribute('contextmenu'));
				if ( $image->getAttribute('dir') )
					$nscript->setAttribute('data-dir', $image->getAttribute('dir'));
				if ( $image->getAttribute('draggable') )
					$nscript->setAttribute('data-draggable', $image->getAttribute('draggable'));
				if ( $image->getAttribute('dropzone') )
					$nscript->setAttribute('data-dropzone', $image->getAttribute('dropzone'));
				if ( $image->getAttribute('hidden') )
					$nscript->setAttribute('data-hidden', $image->getAttribute('hidden'));
				if ( $image->getAttribute('id') )
					$nscript->setAttribute('data-id', $image->getAttribute('id'));
				if ( $image->getAttribute('lang') )
					$nscript->setAttribute('data-lang', $image->getAttribute('lang'));
				if ( $image->getAttribute('spellcheck') )
					$nscript->setAttribute('data-spellcheck', $image->getAttribute('spellcheck'));
				if ( $image->getAttribute('style') )
					$nscript->setAttribute('data-style', $image->getAttribute('style'));
				if ( $image->getAttribute('tabindex') )
					$nscript->setAttribute('data-tabindex', $image->getAttribute('tabindex'));
				if ( $image->getAttribute('title') )
					$nscript->setAttribute('data-title', $image->getAttribute('title'));
				if ( $image->getAttribute('translate') )
					$nscript->setAttribute('data-translate', $image->getAttribute('translate'));
				$nscript->setAttribute('class', 'ewww_webp');
				$image->parentNode->replaceChild($nscript, $image);
				$nscript->appendChild($image);
			}
			if ( empty( $file ) && $image->getAttribute('data-src') && $image->getAttribute('data-thumbnail') ) {
				$file = $image->getAttribute('data-src');
				$thumb = $image->getAttribute('data-thumbnail');
				$ewww_debug .= "checking webp for ngg data-src: $file<br>";
				$filepath = ABSPATH . str_replace( $home_url, '', $file );
				$ewww_debug .= "checking webp for ngg data-thumbnail: $thumb<br>";
				$thumbpath = ABSPATH . str_replace( $home_url, '', $thumb );
				if (file_exists($filepath . '.webp')) {
					$ewww_debug .= "found webp for ngg data-src: $filepath<br>";
					$image->setAttribute('data-webp', $file . '.webp');
				}
				if (file_exists($thumbpath . '.webp')) {
					$ewww_debug .= "found webp for ngg data-thumbnail: $thumbpath<br>";
					$image->setAttribute('data-webp-thumbnail', $thumb . '.webp');
				}
			}
		}
		$links = $html->getElementsByTagName( 'a' );
		foreach ( $links as $link ) {
			$home_url = get_home_url();
			if ( $link->getAttribute( 'data-src' ) && $link->getAttribute( 'data-thumbnail' ) ) {
				$file = $link->getAttribute( 'data-src' );
				$thumb = $link->getAttribute( 'data-thumbnail' );
				$ewww_debug .= "checking webp for ngg data-src: $file<br>";
				$filepath = ABSPATH . str_replace( $home_url, '', $file );
				$ewww_debug .= "checking webp for ngg data-thumbnail: $thumb<br>";
				$thumbpath = ABSPATH . str_replace( $home_url, '', $thumb );
				if ( file_exists( $filepath . '.webp' ) ) {
					$ewww_debug .= "found webp for ngg data-src: $filepath<br>";
					$link->setAttribute( 'data-webp', $file . '.webp' );
				}
				if ( file_exists( $thumbpath . '.webp' ) ) {
					$ewww_debug .= "found webp for ngg data-thumbnail: $thumbpath<br>";
					$link->setAttribute( 'data-webp-thumbnail', $thumb . '.webp' );
				}
				
			}
		}
		$buffer = $html->saveHTML($html->documentElement);
		libxml_clear_errors();
		libxml_use_internal_errors($libxml_previous_error_reporting);
		if ( ! empty( $html_head ) ) {
			$buffer = preg_replace('/<html.+>\s<head>/', $html_head[0], $buffer);
		}
//		$buffer = preg_replace('|</body>\s*</html>|', '', $buffer);
		ewww_image_optimizer_debug_log();
	}
	return $buffer;
}

// set permissions for various operations
function ewww_image_optimizer_manual_permissions( $permissions ) {
	if ( empty( $permissions ) ) {
		return 'edit_others_posts';
	}
	return $permissions;
}

function ewww_image_optimizer_admin_permissions( $permissions ) {
	if ( empty( $permissions ) ) {
		return 'activate_plugins';
	}
	return $permissions;
}

function ewww_image_optimizer_superadmin_permissions( $permissions ) {
	if ( empty( $permissions ) ) {
		return 'manage_network_options';
	}
	return $permissions;
}

function ewwwio_memory( $function ) {
	if ( WP_DEBUG ) {
		global $ewww_memory;
//		$ewww_memory .= $function . ': ' . memory_get_usage(true) . "\n";
	}
}

function ewww_image_optimizer_preinit() {
	global $ewww_debug;
	$ewww_debug .= '<b>ewww_image_optimizer_preinit()</b><br>';
	load_plugin_textdomain(EWWW_IMAGE_OPTIMIZER_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');

	$active_plugins = get_option( 'active_plugins' );
	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, array_flip( get_site_option( 'active_sitewide_plugins' ) ) );
	}

	$ewww_plugins_path = str_replace( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL, '', EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE );

	foreach ($active_plugins as $active_plugin) {
		if ( strpos( $active_plugin, 'nggallery.php' ) ) {
			$ngg = ewww_image_optimizer_get_plugin_version( $ewww_plugins_path . $active_plugin );
			// include the file that loads the nextgen gallery optimization functions
			$ewww_debug .= 'Nextgen version: ' . $ngg['Version'] . '<br>';
			if (preg_match('/^2\./', $ngg['Version'])) { // for Nextgen 2
				$ewww_debug .= "loading nextgen2 support<br>";
				require(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'nextgen2-integration.php');
			} else {
				preg_match( '/\d+\.\d+\.(\d+)/', $ngg['Version'], $nextgen_minor_version);
				if ( ! empty( $nextgen_minor_version[1] ) && $nextgen_minor_version[1] < 14 ) {
					$ewww_debug .= "loading nextgen legacy support<br>";
					require(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'nextgen-integration.php');
				} elseif ( ! empty( $nextgen_minor_version[1] ) && $nextgen_minor_version[1] > 13 ) {
					$ewww_debug .= "loading nextcellent support<br>";
					require(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'nextcellent-integration.php');
				}
			}
		}
		if ( strpos( $active_plugin, 'flag.php' ) ) {
			$ewww_debug .= "loading flagallery support<br>";
			// include the file that loads the grand flagallery optimization functions
			require( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'flag-integration.php' );
		}
	}
}

/**
 * Plugin initialization function
 */
function ewww_image_optimizer_init( $admin = false ) {
	ewwwio_memory( __FUNCTION__ );
	global $ewww_debug;
	global $ewww_memory;
	global $ewww_admin;
	$ewww_admin = $admin;
	$ewww_debug .= "<b>ewww_image_optimizer_init()</b><br>";
	if ( $ewww_admin ) {
		$ewww_debug .= 'we are in the admin, feel free to shout<br>';
	} else {
		$ewww_debug .= 'no admin, be quiet<br>';
	}
	if (get_option('ewww_image_optimizer_version') < EWWW_IMAGE_OPTIMIZER_VERSION) {
		ewww_image_optimizer_install_table();
		ewww_image_optimizer_set_defaults();
		update_option('ewww_image_optimizer_version', EWWW_IMAGE_OPTIMIZER_VERSION);
	}
	ewww_image_optimizer_cloud_init();
	if ( ! $ewww_admin ) {
//		ewww_image_optimizer_tool_init();
	}
	ewwwio_memory( __FUNCTION__ );
}

// Plugin initialization for admin area
function ewww_image_optimizer_admin_init() {
	ewwwio_memory( __FUNCTION__ );
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_admin_init()</b><br>";
	ewww_image_optimizer_init( true );
	if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL)) {
		// set the common network settings if they have been POSTed
		if ( isset( $_POST['ewww_image_optimizer_delay'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'ewww_image_optimizer_options-options' ) ) {
			if (empty($_POST['ewww_image_optimizer_debug'])) $_POST['ewww_image_optimizer_debug'] = '';
			update_site_option('ewww_image_optimizer_debug', $_POST['ewww_image_optimizer_debug']);
			if (empty($_POST['ewww_image_optimizer_jpegtran_copy'])) $_POST['ewww_image_optimizer_jpegtran_copy'] = '';
			update_site_option('ewww_image_optimizer_jpegtran_copy', $_POST['ewww_image_optimizer_jpegtran_copy']);
			if (empty($_POST['ewww_image_optimizer_jpg_lossy'])) $_POST['ewww_image_optimizer_jpg_lossy'] = '';
			update_site_option('ewww_image_optimizer_jpg_lossy', $_POST['ewww_image_optimizer_jpg_lossy']);
			if (empty($_POST['ewww_image_optimizer_png_lossy'])) $_POST['ewww_image_optimizer_png_lossy'] = '';
			update_site_option('ewww_image_optimizer_png_lossy', $_POST['ewww_image_optimizer_png_lossy']);
			if (empty($_POST['ewww_image_optimizer_lossy_fast'])) $_POST['ewww_image_optimizer_lossy_fast'] = '';
			update_site_option('ewww_image_optimizer_lossy_fast', $_POST['ewww_image_optimizer_lossy_fast']);
			if (empty($_POST['ewww_image_optimizer_lossy_skip_full'])) $_POST['ewww_image_optimizer_lossy_skip_full'] = '';
			update_site_option('ewww_image_optimizer_lossy_skip_full', $_POST['ewww_image_optimizer_lossy_skip_full']);
			if (empty($_POST['ewww_image_optimizer_metadata_skip_full'])) $_POST['ewww_image_optimizer_metadata_skip_full'] = '';
			update_site_option('ewww_image_optimizer_metadata_skip_full', $_POST['ewww_image_optimizer_metadata_skip_full']);
			if (empty($_POST['ewww_image_optimizer_delete_originals'])) $_POST['ewww_image_optimizer_delete_originals'] = '';
			update_site_option('ewww_image_optimizer_delete_originals', $_POST['ewww_image_optimizer_delete_originals']);
			if (empty($_POST['ewww_image_optimizer_jpg_to_png'])) $_POST['ewww_image_optimizer_jpg_to_png'] = '';
			update_site_option('ewww_image_optimizer_jpg_to_png', $_POST['ewww_image_optimizer_jpg_to_png']);
			if (empty($_POST['ewww_image_optimizer_png_to_jpg'])) $_POST['ewww_image_optimizer_png_to_jpg'] = '';
			update_site_option('ewww_image_optimizer_png_to_jpg', $_POST['ewww_image_optimizer_png_to_jpg']);
			if (empty($_POST['ewww_image_optimizer_gif_to_png'])) $_POST['ewww_image_optimizer_gif_to_png'] = '';
			update_site_option('ewww_image_optimizer_gif_to_png', $_POST['ewww_image_optimizer_gif_to_png']);
			if (empty($_POST['ewww_image_optimizer_webp'])) $_POST['ewww_image_optimizer_webp'] = '';
			update_site_option('ewww_image_optimizer_webp', $_POST['ewww_image_optimizer_webp']);
			if (empty($_POST['ewww_image_optimizer_jpg_background'])) $_POST['ewww_image_optimizer_jpg_background'] = '';
			update_site_option('ewww_image_optimizer_jpg_background', ewww_image_optimizer_jpg_background($_POST['ewww_image_optimizer_jpg_background']));
			if (empty($_POST['ewww_image_optimizer_jpg_quality'])) $_POST['ewww_image_optimizer_jpg_quality'] = '';
			update_site_option('ewww_image_optimizer_jpg_quality', ewww_image_optimizer_jpg_quality($_POST['ewww_image_optimizer_jpg_quality']));
			if (empty($_POST['ewww_image_optimizer_disable_convert_links'])) $_POST['ewww_image_optimizer_disable_convert_links'] = '';
			update_site_option('ewww_image_optimizer_disable_convert_links', $_POST['ewww_image_optimizer_disable_convert_links']);
			if (empty($_POST['ewww_image_optimizer_cloud_key'])) $_POST['ewww_image_optimizer_cloud_key'] = '';
			update_site_option( 'ewww_image_optimizer_cloud_key', ewww_image_optimizer_cloud_key_sanitize( $_POST['ewww_image_optimizer_cloud_key'] ) );
			if (empty($_POST['ewww_image_optimizer_cloud_jpg'])) $_POST['ewww_image_optimizer_cloud_jpg'] = '';
			update_site_option('ewww_image_optimizer_cloud_jpg', $_POST['ewww_image_optimizer_cloud_jpg']);
			if (empty($_POST['ewww_image_optimizer_cloud_png'])) $_POST['ewww_image_optimizer_cloud_png'] = '';
			update_site_option('ewww_image_optimizer_cloud_png', $_POST['ewww_image_optimizer_cloud_png']);
			if (empty($_POST['ewww_image_optimizer_cloud_png_compress'])) $_POST['ewww_image_optimizer_cloud_png_compress'] = '';
			update_site_option('ewww_image_optimizer_cloud_png_compress', $_POST['ewww_image_optimizer_cloud_png_compress']);
			if (empty($_POST['ewww_image_optimizer_cloud_gif'])) $_POST['ewww_image_optimizer_cloud_gif'] = '';
			update_site_option('ewww_image_optimizer_cloud_gif', $_POST['ewww_image_optimizer_cloud_gif']);
			if (empty($_POST['ewww_image_optimizer_auto'])) $_POST['ewww_image_optimizer_auto'] = '';
			update_site_option('ewww_image_optimizer_auto', $_POST['ewww_image_optimizer_auto']);
			if (empty($_POST['ewww_image_optimizer_aux_paths'])) $_POST['ewww_image_optimizer_aux_paths'] = '';
			update_site_option('ewww_image_optimizer_aux_paths', ewww_image_optimizer_aux_paths_sanitize($_POST['ewww_image_optimizer_aux_paths']));
			if (empty($_POST['ewww_image_optimizer_enable_cloudinary'])) $_POST['ewww_image_optimizer_enable_cloudinary'] = '';
			update_site_option('ewww_image_optimizer_enable_cloudinary', $_POST['ewww_image_optimizer_enable_cloudinary']);
			if (empty($_POST['ewww_image_optimizer_delay'])) $_POST['ewww_image_optimizer_delay'] = '';
			update_site_option('ewww_image_optimizer_delay', intval($_POST['ewww_image_optimizer_delay']));
			if (empty($_POST['ewww_image_optimizer_skip_size'])) $_POST['ewww_image_optimizer_skip_size'] = '';
			update_site_option('ewww_image_optimizer_skip_size', intval($_POST['ewww_image_optimizer_skip_size']));
			if (empty($_POST['ewww_image_optimizer_skip_png_size'])) $_POST['ewww_image_optimizer_skip_png_size'] = '';
			update_site_option('ewww_image_optimizer_skip_png_size', intval($_POST['ewww_image_optimizer_skip_png_size']));
			if (empty($_POST['ewww_image_optimizer_noauto'])) $_POST['ewww_image_optimizer_noauto'] = '';
			update_site_option('ewww_image_optimizer_noauto', $_POST['ewww_image_optimizer_noauto']);
			if (empty($_POST['ewww_image_optimizer_include_media_paths'])) $_POST['ewww_image_optimizer_include_media_paths'] = '';
			update_site_option('ewww_image_optimizer_include_media_paths', $_POST['ewww_image_optimizer_include_media_paths']);
			if (empty($_POST['ewww_image_optimizer_webp_for_cdn'])) $_POST['ewww_image_optimizer_webp_for_cdn'] = '';
			update_site_option('ewww_image_optimizer_webp_for_cdn', $_POST['ewww_image_optimizer_webp_for_cdn']);
			add_action('network_admin_notices', 'ewww_image_optimizer_network_settings_saved');
		}
	}
	// register all the common EWWW IO settings
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_debug');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpegtran_copy');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_lossy');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_png_lossy');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_lossy_fast');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_lossy_skip_full');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_metadata_skip_full');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_delete_originals');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_to_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_png_to_jpg');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_gif_to_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_webp');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_background', 'ewww_image_optimizer_jpg_background');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_quality', 'ewww_image_optimizer_jpg_quality');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_convert_links');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_resume');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_attachments');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_resume');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_attachments');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_type');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_key', 'ewww_image_optimizer_cloud_key_sanitize');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_jpg');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_png_compress');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_gif');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_auto');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_paths', 'ewww_image_optimizer_aux_paths_sanitize');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_enable_cloudinary');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_delay', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_size', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_png_size', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_import_status');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_noauto');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_include_media_paths');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_webp_for_cdn');
	ewww_image_optimizer_exec_init();
	// setup scheduled optimization if the user has enabled it, and it isn't already scheduled
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE && !wp_next_scheduled('ewww_image_optimizer_auto')) {
		$ewww_debug .= "scheduling auto-optimization<br>";
		wp_schedule_event(time(), 'hourly', 'ewww_image_optimizer_auto');
	} elseif (ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE) {
		$ewww_debug .= "auto-optimization already scheduled: " . wp_next_scheduled('ewww_image_optimizer_auto') . "<br>";
	} elseif (wp_next_scheduled('ewww_image_optimizer_auto')) {
		$ewww_debug .= "un-scheduling auto-optimization<br>";
		wp_clear_scheduled_hook('ewww_image_optimizer_auto');
		if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL)) {
			global $wpdb;
			if (function_exists('wp_get_sites')) {
				add_filter('wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0);
				$blogs = wp_get_sites(array(
					'network_id' => $wpdb->siteid,
					'limit' => 10000
				));
				remove_filter('wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0);
			} else {
				$query = "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";
				$blogs = $wpdb->get_results($query, ARRAY_A);
			}
			foreach ($blogs as $blog) {
				switch_to_blog($blog['blog_id']);
				wp_clear_scheduled_hook('ewww_image_optimizer_auto');
			}
			restore_current_blog();
		}
	}
	// require the files that do the bulk processing 
	require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'bulk.php'); 
	require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'aux-optimize.php'); 
	require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'mwebp.php');
	// queue the function that contains custom styling for our progressbars, but only in wp 3.8+ 
	global $wp_version; 
	if ( substr($wp_version, 0, 3) >= 3.8 ) {  
		add_action('admin_enqueue_scripts', 'ewww_image_optimizer_progressbar_style'); 
	}
	ewwwio_memory( __FUNCTION__ );
}

// sets all the tool constants to false
function ewww_image_optimizer_disable_tools() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_disable_tools()</b><br>";
	define('EWWW_IMAGE_OPTIMIZER_JPEGTRAN', false);
	define('EWWW_IMAGE_OPTIMIZER_OPTIPNG', false);
	define('EWWW_IMAGE_OPTIMIZER_PNGOUT', false);
	define('EWWW_IMAGE_OPTIMIZER_GIFSICLE', false);
	ewwwio_memory( __FUNCTION__ );
}

// generates css include for progressbars to match admin style
function ewww_image_optimizer_progressbar_style() {
	wp_add_inline_style('jquery-ui-progressbar', ".ui-widget-header { background-color: " . ewww_image_optimizer_admin_background() . "; }");
	ewwwio_memory( __FUNCTION__ );
}

// determines the background color to use based on the selected theme
function ewww_image_optimizer_admin_background() {
	if (function_exists('wp_add_inline_style')) {
		$user_info = wp_get_current_user();
		switch($user_info->admin_color) {
			case 'midnight':
				return "#e14d43";
			case 'blue':
				return "#096484";
			case 'light':
				return "#04a4cc";
			case 'ectoplasm':
				return "#a3b745";
			case 'coffee':
				return "#c7a589";
			case 'ocean':
				return "#9ebaa0";
			case 'sunrise':
				return "#dd823b";
			default:
				return "#0073aa";
		}
	}
	ewwwio_memory( __FUNCTION__ );
}

// tells WP to ignore the 'large network' detection by filtering the results of wp_is_large_network()
function ewww_image_optimizer_large_network() {
	return false;
}

// adds table to db for storing status of auxiliary images that have been optimized
function ewww_image_optimizer_install_table() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_install_table()</b><br>";
	global $wpdb;
	
	// create a table with 4 columns: an id, the file path, the md5sum, and the optimization results
	$sql = "CREATE TABLE $wpdb->ewwwio_images (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		path text NOT NULL,
		image_md5 VARCHAR(55),
		results VARCHAR(55) NOT NULL,
		gallery VARCHAR(30),
		image_size int UNSIGNED,
		orig_size int UNSIGNED,
		UNIQUE KEY id (id),
		KEY path_image_size (path(255),image_size)
	);";

	// include the upgrade library to initialize a table
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	// make sure some of our options are not autoloaded (since they can be huge)
	$bulk_attachments = get_option('ewww_image_optimizer_bulk_attachments', '');
	delete_option('ewww_image_optimizer_bulk_attachments');
	add_option('ewww_image_optimizer_bulk_attachments', $bulk_attachments, '', 'no');
	$bulk_attachments = get_option('ewww_image_optimizer_flag_attachments', '');
	delete_option('ewww_image_optimizer_flag_attachments');
	add_option('ewww_image_optimizer_flag_attachments', $bulk_attachments, '', 'no');
	$bulk_attachments = get_option('ewww_image_optimizer_ngg_attachments', '');
	delete_option('ewww_image_optimizer_ngg_attachments');
	add_option('ewww_image_optimizer_ngg_attachments', $bulk_attachments, '', 'no');
	$bulk_attachments = get_option('ewww_image_optimizer_aux_attachments', '');
	delete_option('ewww_image_optimizer_aux_attachments');
	add_option('ewww_image_optimizer_aux_attachments', $bulk_attachments, '', 'no');

	// need to re-register the api_key for the sanitize function
	$api_key = ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key');
	unregister_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_key');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_key', 'ewww_image_optimizer_cloud_key_sanitize');
	ewww_image_optimizer_set_option('ewww_image_optimizer_cloud_key', $api_key);

}

// lets the user know their network settings have been saved
function ewww_image_optimizer_network_settings_saved() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_network_settings_saved()</b><br>";
	echo "<div id='ewww-image-optimizer-settings-saved' class='updated fade'><p><strong>" . __('Settings saved', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</strong></p></div>";
}   

// load the class to extend WP_Image_Editor
function ewww_image_optimizer_load_editor($editors) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_load_editor()</b><br>";
	if ( ! class_exists( 'EWWWIO_GD_Editor' ) && ! class_exists( 'EWWWIO_Imagick_Editor' ) )
		include_once( plugin_dir_path(__FILE__) . '/image-editor.php' );
	if ( ! in_array( 'EWWWIO_GD_Editor', $editors ) )
		array_unshift( $editors, 'EWWWIO_GD_Editor' );
	if ( ! in_array( 'EWWWIO_Imagick_Editor', $editors ) )
		array_unshift( $editors, 'EWWWIO_Imagick_Editor' );
	if ( ! in_array( 'EWWWIO_Gmagick_Editor', $editors ) && class_exists( 'WP_Image_Editor_Gmagick' ) )
		array_unshift( $editors, 'EWWWIO_Gmagick_Editor' );
	$ewww_debug .= "loading image editors: " . print_r( $editors, true ) . "<br>";
	ewwwio_memory( __FUNCTION__ );
	return $editors;
}

// register the filter that will remove the image_editor hooks when at attachment is added
function ewww_image_optimizer_add_attachment() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_add_attachment()</b><br>";
	add_filter( 'intermediate_image_sizes_advanced', 'ewww_image_optimizer_image_sizes', 200 );
}

// remove the image editor filter, and add a new filter that will restore it later
function ewww_image_optimizer_image_sizes( $sizes ) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_image_sizes()</b><br>";
	remove_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	add_filter( 'wp_generate_attachment_metadata', 'ewww_image_optimizer_restore_editor_hooks', 1 );
	return $sizes;
}

// restore the image editor filter after the resizes have completed
function ewww_image_optimizer_restore_editor_hooks( $metadata ) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_restore_editor_hooks()</b><br>";
	add_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	return $metadata;
}

// during an upload, remove the W3TC CDN filter and add a new filter with our own wrapper around the W3TC function
function ewww_image_optimizer_handle_upload($params) {
	global $ewww_debug;
	$ewww_debug .= 'ewww_image_optimizer_handle_upload()<br>';
	if ( function_exists( 'w3_instance' ) ) {
		$w3_plugin_cdn = w3_instance('W3_Plugin_Cdn');
		$removed = remove_filter( 'update_attached_file', array( $w3_plugin_cdn, 'update_attached_file' ) );
		add_filter( 'wp_generate_attachment_metadata', 'ewww_image_optimizer_update_attached_file_w3tc', 20, 2 );
	}
	return $params;
}

// this is the delayed wrapper for the W3TC CDN function that runs after optimization (priority 20)
function ewww_image_optimizer_update_attached_file_w3tc( $meta, $id ) {
	global $ewww_debug;
	$ewww_debug .= 'ewww_image_optimizer_update_attached_file_w3tc()<br>';
	list($file_path, $upload_path) = ewww_image_optimizer_attachment_path($meta, $id);
	$w3_plugin_cdn = w3_instance('W3_Plugin_Cdn');
	$w3_plugin_cdn->update_attached_file( $file_path, $id );
	return $meta;
}

// runs scheduled optimization of various auxiliary images
function ewww_image_optimizer_auto() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_auto()</b><br>";
	require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'bulk.php');
	require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'aux-optimize.php');
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE) {
		$ewww_debug .= "running scheduled optimization<br>";
		update_option('ewww_image_optimizer_aux_resume', '');
		update_option('ewww_image_optimizer_aux_attachments', '');
		ewww_image_optimizer_aux_images_script('ewww-image-optimizer-auto');
		ewww_image_optimizer_aux_images_initialize(true);
		$delay = ewww_image_optimizer_get_option('ewww_image_optimizer_delay');		
		$attachments = get_option('ewww_image_optimizer_aux_attachments');
		if ( ! empty( $attachments ) ) {
			foreach ($attachments as $attachment) {
				if (!get_option('ewww_image_optimizer_aux_resume')) {
				//	ewww_image_optimizer_debug_log();
					return;
				}
				ewww_image_optimizer_aux_images_loop($attachment, true);
				if (!empty($delay)) {
					sleep($delay);
				}
			ewww_image_optimizer_debug_log();
			}
		}
		ewww_image_optimizer_aux_images_cleanup(true);
	}
	ewwwio_memory( __FUNCTION__ );
	return;
}

// removes the network settings when the plugin is deactivated
function ewww_image_optimizer_network_deactivate($network_wide) {
	global $wpdb;
	wp_clear_scheduled_hook('ewww_image_optimizer_auto');
	if ($network_wide) {
		$query = $wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid);
		$blogs = $wpdb->get_results($query, ARRAY_A);
		foreach ($blogs as $blog) {
			switch_to_blog($blog['blog_id']);
			wp_clear_scheduled_hook('ewww_image_optimizer_auto');
		}
		restore_current_blog();
	}
}

// adds a global settings page to the network admin settings menu
function ewww_image_optimizer_network_admin_menu() {
	if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(plugin_basename(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE))) {
		// add options page to the settings menu
		$permissions = apply_filters( 'ewww_image_optimizer_superadmin_permissions', '' );
		$ewww_network_options_page = add_submenu_page(
			'settings.php',				//slug of parent
			'EWWW Image Optimizer',			//Title
			'EWWW Image Optimizer',			//Sub-menu title
			$permissions,				//Security
			EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE,				//File to open
			'ewww_image_optimizer_options'	//Function to call
		);
		add_action('admin_footer-' . $ewww_network_options_page, 'ewww_image_optimizer_debug');
	} 
}

// adds the bulk optimize and settings page to the admin menu
function ewww_image_optimizer_admin_menu() {
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	// adds bulk optimize to the media library menu
	$ewww_bulk_page = add_media_page(__('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), $permissions, 'ewww-image-optimizer-bulk', 'ewww_image_optimizer_bulk_preview');
	$ewww_unoptimized_page = add_media_page(__('Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN), __('Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN), $permissions, 'ewww-image-optimizer-unoptimized', 'ewww_image_optimizer_display_unoptimized_media');
	$ewww_webp_migrate_page = add_submenu_page( null, __( 'Migrate WebP Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), __('Migrate WebP Images', EWWW_IMAGE_OPTIMIZER_DOMAIN), $permissions, 'ewww-image-optimizer-webp-migrate', 'ewww_image_optimizer_webp_migrate_preview');
	add_action('admin_footer-' . $ewww_bulk_page, 'ewww_image_optimizer_debug');
	if ( ! function_exists( 'is_plugin_active_for_network' ) || ! is_plugin_active_for_network( plugin_basename( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE ) ) ) { 
		// add options page to the settings menu
		$ewww_options_page = add_options_page(
			'EWWW Image Optimizer',		//Title
			'EWWW Image Optimizer',		//Sub-menu title
			apply_filters( 'ewww_image_optimizer_admin_permissions', '' ),		//Security
			EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE,			//File to open
			'ewww_image_optimizer_options'	//Function to call
		);
		add_action('admin_footer-' . $ewww_options_page, 'ewww_image_optimizer_debug');
	}
	if(is_plugin_active('image-store/ImStore.php') || is_plugin_active_for_network('image-store/ImStore.php')) {
		$ims_menu ='edit.php?post_type=ims_gallery';
		$ewww_ims_page = add_submenu_page($ims_menu, __('Image Store Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), __('Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'ims_change_settings', 'ewww-ims-optimize', 'ewww_image_optimizer_ims');
		add_action('admin_footer-' . $ewww_ims_page, 'ewww_image_optimizer_debug');
	}
}

// check WP Retina images, fixes filenames in the database, and makes sure all derivatives are optimized
function ewww_image_optimizer_retina ( $id, $retina_path ) {
	global $ewww_debug;
	global $wpdb;
	$ewww_debug .= "<b>ewww_image_optimizer_retina()<b><br>";
	$file_info = pathinfo( $retina_path );
	$extension = '.' . $file_info['extension'];
	preg_match ('/-(\d+x\d+)@2x$/', $file_info['filename'], $fileresize);
	$dimensions = explode ( 'x', $fileresize[1]);
	$no_ext_path = $file_info['dirname'] . '/' . preg_replace('/\d+x\d+@2x$/', '', $file_info['filename']) . $dimensions[0] * 2 . 'x' . $dimensions[1] * 2 . '-tmp';
	$temp_path = $no_ext_path . $extension;
	$ewww_debug .= "temp path: $temp_path<br>";
	// check for any orphaned webp retina images also, and fix their paths
	$ewww_debug .= "retina path: $retina_path<br>";
	$webp_path = $temp_path . '.webp';
	$ewww_debug .= "retina webp path: $webp_path<br>";
	if ( file_exists( $webp_path ) ) {
		rename( $webp_path, $retina_path . '.webp' );
	}
	$opt_size = filesize($retina_path);
	$ewww_debug .= "retina size: $opt_size<br>";
	$query = $wpdb->prepare("SELECT id,path FROM $wpdb->ewwwio_images WHERE path = %s AND image_size = '$opt_size'", $temp_path);
	$optimized_query = $wpdb->get_results( $query, ARRAY_A );
	if (!empty($optimized_query)) {
		foreach ( $optimized_query as $image ) {
			if ( $image['path'] != $temp_path ) {
				$ewww_debug .= "{$image['path']} does not match $temp_path, continuing our search<br>";
			} else {
				$already_optimized = $image;
			}
		}
	}
	if (!empty($already_optimized)) {
		// store info on the current image for future reference
		$wpdb->update( $wpdb->ewwwio_images,
			array(
				'path' => $retina_path,
			),
			array(
				'id' => $already_optimized['id'],
			));
	} else {
		ewww_image_optimizer($retina_path, 7, false, false);
	}
	ewwwio_memory( __FUNCTION__ );
}

// list IMS images and optimization status
function ewww_image_optimizer_ims() {
	$ims_columns = get_column_headers('ims_gallery');
	echo "<div class='wrap'><h3>" . __('Image Store Optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</h3>";
	if (empty($_REQUEST['ewww_gid'])) {
		$galleries = get_posts( array(
	                'numberposts' => -1,
	                'post_type' => 'ims_gallery',
			'post_status' => 'any',
			'fields' => 'ids'
	        ));
		sort($galleries, SORT_NUMERIC);
		$gallery_string = implode(',', $galleries);
		echo "<p>" . __('Choose a gallery or', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='upload.php?page=ewww-image-optimizer-bulk&ids=$gallery_string'>" . __('optimize all galleries', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p>";
		echo '<table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>' . __('Gallery ID', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . __('Gallery Name', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . __('Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . __('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th></tr></thead>';
			foreach ($galleries as $gid) {
		                $attachments = get_posts( array(
		                        'numberposts' => -1,
		                        'post_type' => 'ims_image',
					'post_status' => 'any',
		                        'post_mime_type' => 'image',
					'post_parent' => $gid,
					'fields' => 'ids'
		                ));
				$image_count = sizeof($attachments);
				$image_string = implode(',', $attachments);
				$gallery_name = get_the_title($gid);
				echo "<tr><td>$gid</td>";
				echo "<td><a href='edit.php?post_type=ims_gallery&page=ewww-ims-optimize&ewww_gid=$gid'>$gallery_name</a></td>";
				echo "<td>$image_count</td>";
				echo "<td><a href='upload.php?page=ewww-image-optimizer-bulk&ids=$image_string'>" . __('Optimize Gallery', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></td></tr>";
			}
			echo "</table>";
		} else {		
			$gid = $_REQUEST['ewww_gid'];
	                $attachments = get_posts( array(
	                        'numberposts' => -1,
	                        'post_type' => 'ims_image',
				'post_status' => 'any',
	                        'post_mime_type' => 'image',
				'post_parent' => $gid,
				'fields' => 'ids'
	                ));
			sort($attachments, SORT_NUMERIC);
			$image_string = implode(',', $attachments);
			echo "<p><a href='upload.php?page=ewww-image-optimizer-bulk&ids=$image_string'>" . __('Optimize Gallery', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p>";
			echo '<table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>ID</th><th>&nbsp;</th><th>' . __('Title', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . __('Gallery', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . __('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th></tr></thead>';
			$alternate = true;
			foreach ($attachments as $ID) {
				$meta = get_metadata('post', $ID);
				$meta = maybe_unserialize($meta['_wp_attachment_metadata'][0]);
				$image_name = get_the_title($ID);
				$gallery_name = get_the_title($gid);
				$image_url = $meta['sizes']['mini']['url'];
?>				<tr<?php if($alternate) echo " class='alternate'"; ?>><td><?php echo $ID; ?></td>
<?php				echo "<td style='width:80px' class='column-icon'><img src='$image_url' /></td>";
				echo "<td class='title'>$image_name</td>";
				echo "<td>$gallery_name</td><td>";
				ewww_image_optimizer_custom_column('ewww-image-optimizer', $ID);
				echo "</td></tr>";
				$alternate = !$alternate;
			}
			echo '</table>';
		}
	echo '</div>';
	return;	
}

// optimize MyArcade screenshots and thumbs
function ewww_image_optimizer_myarcade_thumbnail( $url ) {
	global $ewww_debug;
	$ewww_debug .= "thumb url passed: $url<br>";
	if ( ! empty( $url ) ) {
        	$thumb_path = str_replace( get_option('siteurl') . '/', ABSPATH, $url );
		$ewww_debug .= "thumb path generated: $thumb_path<br>";
		ewww_image_optimizer( $thumb_path );
	}
	return $url;
}

// enqueue script for webp support with CDNs
function ewww_image_optimizer_webp_load_jquery() {
	wp_enqueue_script('jquery');
}
function ewww_image_optimizer_webp_inline_script() {
?>
<script>
function check_webp_feature(t,a){var e={lossy:"UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA",lossless:"UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==",alpha:"UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA==",animation:"UklGRlIAAABXRUJQVlA4WAoAAAASAAAAAAAAAAAAQU5JTQYAAAD/////AABBTk1GJgAAAAAAAAAAAAAAAAAAAGQAAABWUDhMDQAAAC8AAAAQBxAREYiI/gcA"},A=new Image;A.onload=function(){var t=A.width>0&&A.height>0;a(t)},A.onerror=function(){a(!1)},A.src="data:image/webp;base64,"+e[t]}function ewww_load_images(t){!function(a){t&&(a(".batch-image img, .image-wrapper a, .ngg-pro-masonry-item a").each(function(){var t=a(this).attr("data-webp");"undefined"!=typeof t&&t!==!1&&a(this).attr("data-src",t);var t=a(this).attr("data-webp-thumbnail");"undefined"!=typeof t&&t!==!1&&a(this).attr("data-thumbnail",t)}),a(".image-wrapper a, .ngg-pro-masonry-item a").each(function(){var t=a(this).attr("data-webp");"undefined"!=typeof t&&t!==!1&&a(this).attr("href",t)})),a(".ewww_webp").each(function(){var e=document.createElement("img");t?a(e).attr("src",a(this).attr("data-webp")):a(e).attr("src",a(this).attr("data-img"));var A=a(this).attr("data-align");"undefined"!=typeof A&&A!==!1&&a(e).attr("align",A);var A=a(this).attr("data-alt");"undefined"!=typeof A&&A!==!1&&a(e).attr("alt",A);var A=a(this).attr("data-border");"undefined"!=typeof A&&A!==!1&&a(e).attr("border",A);var A=a(this).attr("data-crossorigin");"undefined"!=typeof A&&A!==!1&&a(e).attr("crossorigin",A);var A=a(this).attr("data-height");"undefined"!=typeof A&&A!==!1&&a(e).attr("height",A);var A=a(this).attr("data-hspace");"undefined"!=typeof A&&A!==!1&&a(e).attr("hspace",A);var A=a(this).attr("data-ismap");"undefined"!=typeof A&&A!==!1&&a(e).attr("ismap",A);var A=a(this).attr("data-longdesc");"undefined"!=typeof A&&A!==!1&&a(e).attr("longdesc",A);var A=a(this).attr("data-usemap");"undefined"!=typeof A&&A!==!1&&a(e).attr("usemap",A);var A=a(this).attr("data-vspace");"undefined"!=typeof A&&A!==!1&&a(e).attr("vspace",A);var A=a(this).attr("data-width");"undefined"!=typeof A&&A!==!1&&a(e).attr("width",A);var A=a(this).attr("data-accesskey");"undefined"!=typeof A&&A!==!1&&a(e).attr("accesskey",A);var A=a(this).attr("data-class");"undefined"!=typeof A&&A!==!1&&a(e).attr("class",A);var A=a(this).attr("data-contenteditable");"undefined"!=typeof A&&A!==!1&&a(e).attr("contenteditable",A);var A=a(this).attr("data-contextmenu");"undefined"!=typeof A&&A!==!1&&a(e).attr("contextmenu",A);var A=a(this).attr("data-dir");"undefined"!=typeof A&&A!==!1&&a(e).attr("dir",A);var A=a(this).attr("data-draggable");"undefined"!=typeof A&&A!==!1&&a(e).attr("draggable",A);var A=a(this).attr("data-dropzone");"undefined"!=typeof A&&A!==!1&&a(e).attr("dropzone",A);var A=a(this).attr("data-hidden");"undefined"!=typeof A&&A!==!1&&a(e).attr("hidden",A);var A=a(this).attr("data-id");"undefined"!=typeof A&&A!==!1&&a(e).attr("id",A);var A=a(this).attr("data-lang");"undefined"!=typeof A&&A!==!1&&a(e).attr("lang",A);var A=a(this).attr("data-spellcheck");"undefined"!=typeof A&&A!==!1&&a(e).attr("spellcheck",A);var A=a(this).attr("data-style");"undefined"!=typeof A&&A!==!1&&a(e).attr("style",A);var A=a(this).attr("data-tabindex");"undefined"!=typeof A&&A!==!1&&a(e).attr("tabindex",A);var A=a(this).attr("data-title");"undefined"!=typeof A&&A!==!1&&a(e).attr("title",A);var A=a(this).attr("data-translate");"undefined"!=typeof A&&A!==!1&&a(e).attr("translate",A),a(this).after(e)})}(jQuery)}check_webp_feature("alpha",ewww_load_images);
</script>
<?php
}

// enqueue custom jquery stylesheet for bulk optimizer
function ewww_image_optimizer_media_scripts($hook) {
	if ($hook == 'upload.php') {
		wp_enqueue_script('jquery-ui-tooltip');
		wp_enqueue_style('jquery-ui-tooltip-custom', plugins_url('jquery-ui-1.10.1.custom.css', __FILE__));
	}
}

// used to output any debug messages available
function ewww_image_optimizer_debug() {
	global $ewww_debug;
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_debug')) echo '<div style="background-color:#ffff99;position:relative;bottom:60px;padding:5px 20px 10px;margin:0 0 15px 160px"><h3>Debug Log</h3>' . $ewww_debug . '</div>';
	ewwwio_memory( __FUNCTION__ );
}

// used to output debug messages to a logfile in the plugin folder in cases where output to the screen is a bad idea
function ewww_image_optimizer_debug_log() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_debug_log()</b><br>";
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_debug')) {
		$timestamp = date('y-m-d h:i:s.u') . "  ";
		if (!file_exists(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log'))
			touch(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log');
		$ewww_debug_log = str_replace('<br>', "\n", $ewww_debug);
		file_put_contents(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log', $timestamp . $ewww_debug_log, FILE_APPEND);
	}
	$ewww_debug = '';
	ewwwio_memory( __FUNCTION__ );
}

// adds a link on the Plugins page for the EWWW IO settings
function ewww_image_optimizer_settings_link($links) {
	// load the html for the settings link
	$settings_link = '<a href="options-general.php?page=' . plugin_basename(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE) . '">' . __('Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a>';
	// load the settings link into the plugin links array
	array_unshift($links, $settings_link);
	// send back the plugin links array
	return $links;
}

// check for GD support of both PNG and JPG
function ewww_image_optimizer_gd_support() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_gd_support()</b><br>";
	if (function_exists('gd_info')) {
		$gd_support = gd_info();
		$ewww_debug .= "GD found, supports: <br>"; 
		foreach ($gd_support as $supports => $supported) {
			 $ewww_debug .= "$supports: $supported<br>";
		}
		ewwwio_memory( __FUNCTION__ );
		if ( ( ! empty( $gd_support["JPEG Support"] ) || ! empty( $gd_support["JPG Support"] ) ) && ! empty( $gd_support["PNG Support"] ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}

function ewww_image_optimizer_aux_paths_sanitize ($input) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_aux_paths_sanitize()</b><br>";
	if (empty($input)) {
		return '';
	}
	$path_array = array();
	$paths = explode("\n", $input);
	foreach ($paths as $path) {
		$path = sanitize_text_field($path);
		$ewww_debug .= "validating auxiliary path: $path <br>";
		// retrieve the location of the wordpress upload folder
		$upload_dir = wp_upload_dir();
		// retrieve the path of the upload folder
		$upload_path = trailingslashit($upload_dir['basedir']);
		if (is_dir($path) && (strpos($path, trailingslashit(ABSPATH)) === 0 || strpos($path, $upload_path) === 0)) {
			$path_array[] = $path;
		}
	}
	ewww_image_optimizer_debug_log();
	ewwwio_memory( __FUNCTION__ );
	return $path_array;
}

// replacement for escapeshellarg() that won't kill non-ASCII characters
function ewww_image_optimizer_escapeshellarg( $arg ) {
	global $ewww_debug;
	if ( PHP_OS === 'WINNT' ) {
		$safe_arg = '"' . $arg . '"';
	} else {
		$safe_arg = "'" . str_replace("'", "'\"'\"'", $arg) . "'";
	}
	ewwwio_memory( __FUNCTION__ );
	return $safe_arg;
}

// Retrieves/sanitizes jpg background fill setting or returns null for png2jpg conversions
function ewww_image_optimizer_jpg_background ($background = null) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_jpg_background()</b><br>";
	if ( $background === null ) {
		// retrieve the user-supplied value for jpg background color
		$background = ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_background');
	}
	//verify that the supplied value is in hex notation
	if (preg_match('/^\#*([0-9a-fA-F]){6}$/',$background)) {
		// we remove a leading # symbol, since we take care of it later
		preg_replace('/#/','',$background);
		// send back the verified, cleaned-up background color
		$ewww_debug .= "background: $background<br>";
		ewwwio_memory( __FUNCTION__ );
		return $background;
	} else {
		// send back a blank value
		ewwwio_memory( __FUNCTION__ );
		return NULL;
	}
}

// Retrieves/sanitizes the jpg quality setting for png2jpg conversion or returns null
function ewww_image_optimizer_jpg_quality ($quality = null) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_jpg_quality()</b><br>";
	if ( $quality === null ) {
		// retrieve the user-supplied value for jpg quality
		$quality = ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_quality');
	}
	// verify that the quality level is an integer, 1-100
	if (preg_match('/^(100|[1-9][0-9]?)$/',$quality)) {
		// send back the valid quality level
		ewwwio_memory( __FUNCTION__ );
		return $quality;
	} else {
		// send back nothing
		ewwwio_memory( __FUNCTION__ );
		return NULL;
	}
}

/**
 * Manually process an image from the Media Library
 */
function ewww_image_optimizer_manual() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_manual()</b><br>";
	// check permissions of current user
	$permissions = apply_filters( 'ewww_image_optimizer_manual_permissions', '' );
	if ( FALSE === current_user_can( $permissions ) ) {
		// display error message if insufficient permissions
		wp_die(__('You don\'t have permission to optimize images.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	}
	// make sure we didn't accidentally get to this page without an attachment to work on
	if ( FALSE === isset($_GET['ewww_attachment_ID'])) {
		// display an error message since we don't have anything to work on
		wp_die(__('No attachment ID was provided.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	}
	// store the attachment ID value
	$attachment_ID = intval($_GET['ewww_attachment_ID']);
	// retrieve the existing attachment metadata
	$original_meta = wp_get_attachment_metadata( $attachment_ID );
	// if the call was to optimize...
	if ($_REQUEST['action'] === 'ewww_image_optimizer_manual_optimize') {
		// call the optimize from metadata function and store the resulting new metadata
		$new_meta = ewww_image_optimizer_resize_from_meta_data($original_meta, $attachment_ID);
	} elseif ($_REQUEST['action'] === 'ewww_image_optimizer_manual_restore') {
		$new_meta = ewww_image_optimizer_restore_from_meta_data($original_meta, $attachment_ID);
	}
	// update the attachment metadata in the database
	wp_update_attachment_metadata( $attachment_ID, $new_meta );
	// store the referring webpage location
	$sendback = wp_get_referer();
	// sanitize the referring webpage location
	$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);
	// send the user back where they came from
	wp_redirect($sendback);
	// we are done, nothing to see here
	ewwwio_memory( __FUNCTION__ );
	exit(0);
}

/**
 * Manually restore a converted image
 */
function ewww_image_optimizer_restore_from_meta_data($meta, $id) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_restore_from_meta_data()</b><br>";
	// get the filepath
	list($file_path, $upload_path) = ewww_image_optimizer_attachment_path($meta, $id);
	$file_path = get_attached_file($id);
	if (!empty($meta['converted'])) {
		if (file_exists($meta['orig_file'])) {
			// update the filename in the metadata
			$meta['file'] = $meta['orig_file'];
			// update the optimization results in the metadata
			$meta['ewww_image_optimizer'] = __('Original Restored', EWWW_IMAGE_OPTIMIZER_DOMAIN);
			$meta['orig_file'] = $file_path;
			$meta['converted'] = 0;
			unlink($meta['orig_file']);
			$meta['file'] = str_replace($upload_path, '', $meta['file']);
			// if we don't already have the update attachment filter
			if (FALSE === has_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment'))
				// add the update attachment filter
				add_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2);
		} else {
			remove_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10);
		}
	}
	if (isset($meta['sizes']) ) {
		// process each resized version
		$processed = array();
		// meta sizes don't contain a path, so we calculate one
		$base_dir = dirname($file_path) . '/';
		foreach($meta['sizes'] as $size => $data) {
			// check through all the sizes we've processed so far
			foreach($processed as $proc => $scan) {
				// if a previous resize had identical dimensions
				if ($scan['height'] == $data['height'] && $scan['width'] == $data['width'] && isset($meta['sizes'][$proc]['converted'])) {
					// point this resize at the same image as the previous one
					$meta['sizes'][$size]['file'] = $meta['sizes'][$proc]['file'];
				}
			}
			if (isset($data['converted'])) {
				// if this is a unique size
				if (file_exists($base_dir . $data['orig_file'])) {
					// update the filename
					$meta['sizes'][$size]['file'] = $data['orig_file'];
					// update the optimization results
					$meta['sizes'][$size]['ewww_image_optimizer'] = __('Original Restored', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$meta['sizes'][$size]['orig_file'] = $data['file'];
					$meta['sizes'][$size]['converted'] = 0;
						// if we don't already have the update attachment filter
						if (FALSE === has_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment'))
							// add the update attachment filter
							add_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2);
					unlink($base_dir . $data['file']);
				}
				// store info on the sizes we've processed, so we can check the list for duplicate sizes
				$processed[$size]['width'] = $data['width'];
				$processed[$size]['height'] = $data['height'];
			}		
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return $meta;
}

// deletes 'orig_file' when an attachment is being deleted
function ewww_image_optimizer_delete ($id) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_delete()</b><br>";
	global $wpdb;
	// retrieve the image metadata
	$meta = wp_get_attachment_metadata($id);
	// if the attachment has an original file set
	if (!empty($meta['orig_file'])) {
		unset($rows);
		// get the filepath from the metadata
		$file_path = $meta['orig_file'];
		// get the filename
		$filename = basename($file_path);
		// delete any residual webp versions
		$webpfile = $filename . '.webp';
		$webpfileold = preg_replace( '/\.\w+$/', '.webp', $filename );
		if ( file_exists( $webpfile) ) {
			unlink( $webpfile );
		}
		if ( file_exists( $webpfileold) ) {
			unlink( $webpfileold );
		}
		// retrieve any posts that link the original image
		$esql = "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%$filename%'";
		$rows = $wpdb->get_row($esql);
		// if the original file still exists and no posts contain links to the image
		if (file_exists($file_path) && empty($rows)) {
			unlink($file_path);
			$wpdb->delete($wpdb->ewwwio_images, array('path' => $file_path));
		}
	}
	// remove the regular image from the ewwwio_images tables
	list($file_path, $upload_path) = ewww_image_optimizer_attachment_path($meta, $id);
	$wpdb->delete($wpdb->ewwwio_images, array('path' => $file_path));
	// resized versions, so we can continue
	if (isset($meta['sizes']) ) {
		// one way or another, $file_path is now set, and we can get the base folder name
		$base_dir = dirname($file_path) . '/';
		// check each resized version
		foreach($meta['sizes'] as $size => $data) {
			// delete any residual webp versions
			$webpfile = $base_dir . $data['file'] . '.webp';
			$webpfileold = preg_replace( '/\.\w+$/', '.webp', $base_dir . $data['file'] );
			if ( file_exists( $webpfile) ) {
				unlink( $webpfile );
			}
			if ( file_exists( $webpfileold) ) {
				unlink( $webpfileold );
			}
			$wpdb->delete($wpdb->ewwwio_images, array('path' => $base_dir . $data['file']));
			// if the original resize is set, and still exists
			if (!empty($data['orig_file']) && file_exists($base_dir . $data['orig_file'])) {
				unset($srows);
				// retrieve the filename from the metadata
				$filename = $data['orig_file'];
				// retrieve any posts that link the image
				$esql = "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%$filename%'";
				$srows = $wpdb->get_row($esql);
				// if there are no posts containing links to the original, delete it
				if(empty($srows)) {
					unlink($base_dir . $data['orig_file']);
					$wpdb->delete($wpdb->ewwwio_images, array('path' => $base_dir . $data['orig_file']));
				}
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return;
}

function ewww_image_optimizer_cloud_key_sanitize ( $key ) {
	global $ewww_debug;
	$key = trim( $key );
	$ewww_debug .= "<b>ewww_image_optimizer_cloud_key_sanitize()</b><br>";
	if ( ewww_image_optimizer_cloud_verify( false, $key ) ) {
		$ewww_debug .= "sanitize (verification) successful<br>";
		ewwwio_memory( __FUNCTION__ );
		return $key;
	} else {
		$ewww_debug .= "sanitize (verification) failed<br>";
		ewwwio_memory( __FUNCTION__ );
		return '';
	}
}

// turns on the cloud settings when they are all disabled
function ewww_image_optimizer_cloud_enable () {
	ewww_image_optimizer_set_option('ewww_image_optimizer_cloud_jpg', true);
	ewww_image_optimizer_set_option('ewww_image_optimizer_cloud_png', true);
	ewww_image_optimizer_set_option('ewww_image_optimizer_cloud_gif', true);
}

// adds our version to the useragent for http requests
function ewww_image_optimizer_cloud_useragent ( $useragent ) {
	$useragent .= ' EWWW/' . EWWW_IMAGE_OPTIMIZER_VERSION;
	ewwwio_memory( __FUNCTION__ );
	return $useragent;
}
 
// submits the api key for verification
function ewww_image_optimizer_cloud_verify ( $cache = true, $api_key = '' ) {
	global $ewww_debug;
	global $ewww_cloud_ip;
	global $ewww_cloud_transport;
	$ewww_debug .= "<b>ewww_image_optimizer_cloud_verify()</b><br>";
	if ( empty( $api_key ) ) {
		$api_key = ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key');
	}
	if (empty($api_key)) {
		update_site_option('ewww_image_optimizer_cloud_jpg', '');
		update_site_option('ewww_image_optimizer_cloud_png', '');
		update_site_option('ewww_image_optimizer_cloud_gif', '');
		update_option('ewww_image_optimizer_cloud_jpg', '');
		update_option('ewww_image_optimizer_cloud_png', '');
		update_option('ewww_image_optimizer_cloud_gif', '');
		return false;
	}
	add_filter( 'http_headers_useragent', 'ewww_image_optimizer_cloud_useragent' );
	$prev_verified = get_option('ewww_image_optimizer_cloud_verified');
	$last_checked = get_option('ewww_image_optimizer_cloud_last');
	$ewww_cloud_ip = get_option('ewww_image_optimizer_cloud_ip');
	if ($cache && $prev_verified && $last_checked + 86400 > time() && !empty($ewww_cloud_ip)) {
		$ewww_debug .= "using cached IP: $ewww_cloud_ip<br>";
		if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' )  && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) ) {
			ewww_image_optimizer_cloud_enable();
		}
		return $prev_verified;	
	} else {
		$servers = gethostbynamel('optimize.exactlywww.com');
		if ( empty ( $servers ) ) {
			$ewww_debug .= "unable to resolve servers<br>";
			return false;
		}
		$ewww_cloud_transport = 'https';
		foreach ($servers as $ip) {
			$url = "$ewww_cloud_transport://$ip/verify/";
			$result = wp_remote_post($url, array(
				'timeout' => 5,
				'sslverify' => false,
				'body' => array('api_key' => $api_key)
			));
			if (is_wp_error($result)) {
				$ewww_cloud_transport = 'http';
				$error_message = $result->get_error_message();
				$ewww_debug .= "verification failed: $error_message <br>";
			} elseif (!empty($result['body']) && preg_match('/(great|exceeded)/', $result['body'])) {
				$verified = $result['body'];
				$ewww_cloud_ip = $ip;
				$ewww_debug .= "verification success via: $ewww_cloud_transport://$ip <br>";
				break;
			} else {
				$ewww_debug .= "verification failed via: $ip <br>" . print_r($result, true) . "<br>";
			}
		}
	}
	do_action( 'ewww_image_optimizer_verify', $result['body'] );
	if (empty($verified)) {
		ewwwio_memory( __FUNCTION__ );
		return FALSE;
	} else {
		if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' )  && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) ) {
			ewww_image_optimizer_cloud_enable();
		}
		update_option ( 'ewww_image_optimizer_cloud_verified', $verified );
		update_option ( 'ewww_image_optimizer_cloud_last', time() );
		update_option ( 'ewww_image_optimizer_cloud_ip', $ewww_cloud_ip );
		$ewww_debug .= "verification body contents: " . $result['body'] . "<br>";
		ewwwio_memory( __FUNCTION__ );
		return $verified;
	}
}

// checks the provided api key for quota information
function ewww_image_optimizer_cloud_quota() {
	global $ewww_debug;
	global $ewww_cloud_ip;
	global $ewww_cloud_transport;
	$ewww_debug .= "<b>ewww_image_optimizer_cloud_quota()</b><br>";
	$api_key = ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key');
	$url = "$ewww_cloud_transport://$ewww_cloud_ip/quota/";
	$result = wp_remote_post($url, array(
		'timeout' => 5,
		'sslverify' => false,
		'body' => array('api_key' => $api_key)
	));
	if (is_wp_error($result)) {
		$error_message = $result->get_error_message();
		$ewww_debug .= "quota request failed: $error_message <br>";
		ewwwio_memory( __FUNCTION__ );
		return '';
	} elseif (!empty($result['body'])) {
		$ewww_debug .= "quota data retrieved: " . $result['body'] . "<br>";
		$quota = explode(' ', $result['body']);
		ewwwio_memory( __FUNCTION__ );
		if ( $quota[0] == 0 && $quota[1] > 0 ) {
			return sprintf(_n('optimized %1$d images, usage will reset in %2$d day.', 'optimized %1$d images, usage will reset in %2$d days.', $quota[2], EWWW_IMAGE_OPTIMIZER_DOMAIN), $quota[1], $quota[2]);
		} elseif ( $quota[0] == 0 && $quota[1] < 0 ) {
			return sprintf(_n('%1$d image credit remaining.', '%1$d image credits remaining.', $quota[1], EWWW_IMAGE_OPTIMIZER_DOMAIN), abs( $quota[1] ));
		} else {
			return sprintf(_n('used %1$d of %2$d, usage will reset in %3$d day.', 'used %1$d of %2$d, usage will reset in %3$d days.', $quota[2], EWWW_IMAGE_OPTIMIZER_DOMAIN), $quota[1], $quota[0], $quota[2]);
		}
	}
}

/* submits an image to the cloud optimizer and saves the optimized image to disk
 *
 * Returns an array of the $file, $results, $converted to tell us if an image changes formats, and the $original file if it did.
 *
 * @param   string $file		Full absolute path to the image file
 * @param   string $type		mimetype of $file
 * @param   boolean $convert		true says we want to attempt conversion of $file
 * @param   string $newfile		filename of new converted image
 * @param   string $newtype		mimetype of $newfile
 * @param   boolean $fullsize		is this the full-size original?
 * @param   array $jpg_params		r, g, b values and jpg quality setting for conversion
 * @returns array
*/
function ewww_image_optimizer_cloud_optimizer($file, $type, $convert = false, $newfile = null, $newtype = null, $fullsize = false, $jpg_params = array('r' => '255', 'g' => '255', 'b' => '255', 'quality' => null)) {
	global $ewww_debug;
	if ( ! ewww_image_optimizer_cloud_verify(false) ) { 
		return array($file, false, 'key verification failed', 0);
	}
	global $ewww_exceed;
	global $ewww_cloud_ip;
	global $ewww_cloud_transport;
	$ewww_debug .= "<b>ewww_image_optimizer_cloud_optimizer()</b><br>";
	if ( $ewww_exceed ) {
		$ewww_debug .= "license exceeded, image not processed<br>";
		return array($file, false, 'exceeded', 0);
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_metadata_skip_full' ) && $fullsize ) {
		$metadata = 1;
	} elseif ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) ){
        	// don't copy metadata
                $metadata = 0;
        } else {
                // copy all the metadata
                $metadata = 1;
        }
	if (empty($convert)) {
		$convert = 0;
	} else {
		$convert = 1;
	}
	if ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') && $fullsize ) {
		$lossy = 0;
	} elseif ( $type == 'image/png' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ) ) {
		$lossy = 1;
	} elseif ( $type == 'image/jpeg' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_lossy' ) ) {
		$lossy = 1;
	} else {
		$lossy = 0;
	}
	if ( $lossy && ewww_image_optimizer_get_option( 'ewww_image_optimizer_lossy_fast' ) ) {
		$lossy_fast = 1;
	} else {
		$lossy_fast = 0;
	}
	if ( $newtype == 'image/webp' ) {
		$webp = 1;
	} else {
		$webp = 0;
	}
	$ewww_debug .= "file: $file<br>";
	$ewww_debug .= "type: $type<br>";
	$ewww_debug .= "convert: $convert<br>";
	$ewww_debug .= "newfile: $newfile<br>";
	$ewww_debug .= "newtype: $newtype<br>";
	$ewww_debug .= "webp: $webp<br>";
	$ewww_debug .= "jpg_params: " . print_r($jpg_params, true) . " <br>";
	$api_key = ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key');
	$url = "$ewww_cloud_transport://$ewww_cloud_ip/";
	$boundary = wp_generate_password(24, false);

	$headers = array(
        	'content-type' => 'multipart/form-data; boundary=' . $boundary,
		'timeout' => 90,
		'httpversion' => '1.0',
		'blocking' => true
		);
	$post_fields = array(
		'oldform' => 1, 
		'convert' => $convert, 
		'metadata' => $metadata, 
		'api_key' => $api_key,
		'red' => $jpg_params['r'],
		'green' => $jpg_params['g'],
		'blue' => $jpg_params['b'],
		'quality' => $jpg_params['quality'],
		'compress' => ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png_compress'),
		'lossy' => $lossy,
		'lossy_fast' => $lossy_fast,
		'webp' => $webp,
	);

	$payload = '';
	foreach ($post_fields as $name => $value) {
        	$payload .= '--' . $boundary;
	        $payload .= "\r\n";
	        $payload .= 'Content-Disposition: form-data; name="' . $name .'"' . "\r\n\r\n";
	        $payload .= $value;
	        $payload .= "\r\n";
	}

	$payload .= '--' . $boundary;
	$payload .= "\r\n";
	$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file) . '"' . "\r\n";
	$payload .= 'Content-Type: ' . $type . "\r\n";
	$payload .= "\r\n";
	$payload .= file_get_contents($file);
	$payload .= "\r\n";
	$payload .= '--' . $boundary;
	$payload .= 'Content-Disposition: form-data; name="submitHandler"' . "\r\n";
	$payload .= "\r\n";
	$payload .= "Upload\r\n";
	$payload .= '--' . $boundary . '--';

	// retrieve the time when the optimizer starts
//	$started = microtime(true);
	$response = wp_remote_post($url, array(
		'timeout' => 90,
		'headers' => $headers,
		'sslverify' => false,
		'body' => $payload,
		));
//	$elapsed = microtime(true) - $started;
//	$ewww_debug .= "processing image via cloud took $elapsed seconds<br>";
	if (is_wp_error($response)) {
		$error_message = $response->get_error_message();
		$ewww_debug .= "optimize failed: $error_message <br>";
		return array($file, false, 'cloud optimize failed', 0);
	} else {
		$tempfile = $file . ".tmp";
		file_put_contents($tempfile, $response['body']);
		$orig_size = filesize($file);
		$newsize = $orig_size;
		$ewww_debug .= "cloud results: $newsize (new) vs. $orig_size (original)<br>";
		$converted = false;
		$msg = '';
		if (preg_match('/exceeded/', $response['body'])) {
			$ewww_debug .= "License Exceeded<br>";
					global $ewww_exceed;
					$ewww_exceed = true;
			$msg = 'exceeded';
			unlink($tempfile);
		} elseif (ewww_image_optimizer_mimetype($tempfile, 'i') == $type) {
			$newsize = filesize($tempfile);
			rename($tempfile, $file);
		} elseif (ewww_image_optimizer_mimetype($tempfile, 'i') == 'image/webp') {
			$newsize = filesize($tempfile);
			rename($tempfile, $newfile);
		} elseif (ewww_image_optimizer_mimetype($tempfile, 'i') == $newtype) {
			$converted = true;
			$newsize = filesize($tempfile);
			rename($tempfile, $newfile);
			$file = $newfile;
		} else {
			unlink($tempfile);
		}
	ewwwio_memory( __FUNCTION__ );
		return array($file, $converted, $msg, $newsize);
	}
}

// check the database to see if we've done this image before
function ewww_image_optimizer_check_table ($file, $orig_size) {
	global $wpdb;
	global $ewww_debug;
	$already_optimized = false;
	$ewww_debug .= "<b>ewww_image_optimizer_check_table()</b><br>";
	$query = $wpdb->prepare("SELECT path,results FROM $wpdb->ewwwio_images WHERE path = %s AND image_size = '$orig_size'", $file);
	$already_optimized = $wpdb->get_results($query, ARRAY_A);
	if (!empty($already_optimized) && empty($_REQUEST['ewww_force'])) {
		$prev_string = " - " . __('Previously Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		foreach ( $already_optimized as $image ) {
			if ( $image['path'] != $file ) {
				$ewww_debug .= "{$image['path']} does not match $file, continuing our search<br>";
			} else {
				if ( preg_match( '/' . __('License exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '/', $image['results'] ) ) {
					return;
				}
				$already_optimized = preg_replace("/$prev_string/", '', $image['results']);
				$already_optimized = $already_optimized . $prev_string;
				$ewww_debug .= "already optimized: {$image['path']} - $already_optimized<br>";
				ewwwio_memory( __FUNCTION__ );
				return $already_optimized;
			}
		}
	}
}

// receives a path, results, optimized size, and an original size to insert into ewwwwio_images table
// if this is a $new image, copy the result stored in the database
function ewww_image_optimizer_update_table ($attachment, $opt_size, $orig_size, $preserve_results = false) {
	global $wpdb;
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_update_table()</b><br>";
	$query = $wpdb->prepare("SELECT id,orig_size,results,path FROM $wpdb->ewwwio_images WHERE path = %s", $attachment);
	$optimized_query = $wpdb->get_results($query, ARRAY_A);
	if (!empty($optimized_query)) {
		foreach ( $optimized_query as $image ) {
			if ( $image['path'] != $attachment ) {
				$ewww_debug .= "{$image['path']} does not match $attachment, continuing our search<br>";
			} else {
				$already_optimized = $image;
			}
		}
	}
	$ewww_debug .= "savings: $opt_size (new) vs. $orig_size (orig)<br>";
	if (!empty($already_optimized['results']) && $preserve_results && $opt_size === $orig_size) {
		$results_msg = $already_optimized['results'];
	} elseif ($opt_size >= $orig_size) {
		$ewww_debug .= "original and new file are same size (or something weird made the new one larger), no savings<br>";
		$results_msg = __('No savings', EWWW_IMAGE_OPTIMIZER_DOMAIN);
	} else {
		// calculate how much space was saved
		$savings = intval($orig_size) - intval($opt_size);
		// convert it to human readable format
		$savings_str = size_format($savings, 1);
		// replace spaces and extra decimals with proper html entity encoding
		$savings_str = preg_replace('/\.0 B /', ' B', $savings_str);
		$savings_str = str_replace(' ', '&nbsp;', $savings_str);
		// determine the percentage savings
		$percent = 100 - (100 * ($opt_size / $orig_size));
		// use the percentage and the savings size to output a nice message to the user
		$results_msg = sprintf(__("Reduced by %01.1f%% (%s)", EWWW_IMAGE_OPTIMIZER_DOMAIN),
			$percent,
			$savings_str
		);
		$ewww_debug .= "original and new file are different size: $results_msg<br>";
	}
	if (empty($already_optimized)) {
		$ewww_debug .= "creating new record, path: $attachment, size: " . $opt_size . "<br>";
		// store info on the current image for future reference
		$wpdb->insert( $wpdb->ewwwio_images, array(
				'path' => $attachment,
				'image_size' => $opt_size,
				'orig_size' => $orig_size,
				'results' => $results_msg,
			));
	} else {
		$ewww_debug .= "updating existing record (" . $already_optimized['id'] . "), path: $attachment, size: " . $opt_size . "<br>";
		// store info on the current image for future reference
		$wpdb->update( $wpdb->ewwwio_images,
			array(
				'image_size' => $opt_size,
				'results' => $results_msg,
			),
			array(
				'id' => $already_optimized['id'],
			));
	}
	ewwwio_memory( __FUNCTION__ );
	$wpdb->flush();
	ewwwio_memory( __FUNCTION__ );
	return $results_msg;
}

// called by javascript to process each image in the loop
function ewww_image_optimizer_aux_images_loop( $attachment = null, $auto = false ) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_aux_images_loop()</b><br>";
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! $auto && ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) ) {
		wp_die( __( 'Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	if ( ! empty( $_REQUEST['ewww_sleep'] ) ) {
		sleep( $_REQUEST['ewww_sleep'] );
	}
	// retrieve the time when the optimizer starts
	$started = microtime( true );
	if ( ini_get( 'max_execution_time' ) < 60 ) {
		set_time_limit ( 0 );
	}
	// get the path of the current attachment
	if ( empty( $attachment ) ) {
		$attachment = $_POST['ewww_attachment'];
	}
	$attachment = preg_replace( ":\\\':", "'", $attachment );
	// get the 'aux attachments' with a list of attachments remaining
	$attachments = get_option( 'ewww_image_optimizer_aux_attachments' );
	// do the optimization for the current image
	$results = ewww_image_optimizer( $attachment );
	// remove the first element fromt the $attachments array
	if ( ! empty( $attachments ) ) {
		array_shift( $attachments );
	}
	// store the updated list of attachment IDs back in the 'bulk_attachments' option
	update_option( 'ewww_image_optimizer_aux_attachments', $attachments );
	if ( ! $auto ) {
		// output the path
		printf( "<p>" . __('Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <strong>%s</strong><br>", esc_html($attachment) );
		// tell the user what the results were for the original image
		printf( "%s<br>", $results[1] );
		// calculate how much time has elapsed since we started
		$elapsed = microtime(true) - $started;
		// output how much time has elapsed since we started
		printf(__('Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>", $elapsed);
		if ( get_site_option( 'ewww_image_optimizer_debug' ) ) {
			echo '<div style="background-color:#ffff99;">' . $ewww_debug . '</div>';
		}
		ewwwio_memory( __FUNCTION__ );
		die();
	}
	ewwwio_memory( __FUNCTION__ );
}

// processes metadata and looks for any webp version to insert in the meta
function ewww_image_optimizer_update_attachment_metadata($meta, $ID) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_update_attachment_metadata()</b><br>";
	$ewww_debug .= "attachment id: $ID<br>";
	list($file_path, $upload_path) = ewww_image_optimizer_attachment_path($meta, $ID);
	// don't do anything else if the attachment path can't be retrieved
	if (!is_file($file_path)) {
		$ewww_debug .= "could not retrieve path<br>";
		return $meta;
	}
	$ewww_debug .= "retrieved file path: $file_path<br>";
	if ( is_file( $file_path . '.webp' ) ) {
		$meta['sizes']['webp-full'] = array(
			'file' => pathinfo( $file_path, PATHINFO_BASENAME ) . '.webp',
			'width' => 0,
			'height' => 0,
			'mime-type' => 'image/webp',
		);
		
	}
	// if the file was converted
	// resized versions, so we can continue
	if (isset($meta['sizes']) ) {
		$ewww_debug .= "processing resizes<br>";
		// meta sizes don't contain a path, so we use the foldername from the original to generate one
		$base_dir = dirname($file_path) . '/';
		// process each resized version
		$processed = array();
		foreach($meta['sizes'] as $size => $data) {
			$resize_path = $base_dir . $data['file'];
			// update the webp paths
			if ( is_file( $resize_path . '.webp' ) ) {
				$meta['sizes']['webp-' . $size] = array(
					'file' => $data['file'] . '.webp',
					'width' => 0,
					'height' => 0,
					'mime-type' => 'image/webp',
				);
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
	// send back the updated metadata
	return $meta;
}

/**
 * Read the image paths from an attachment's meta data and process each image
 * with ewww_image_optimizer().
 *
 * This method also adds a `ewww_image_optimizer` meta key for use in the media library 
 * and may add a 'converted' and 'orig_file' key if conversion is enabled.
 *
 * Called after `wp_generate_attachment_metadata` is completed.
 */
function ewww_image_optimizer_resize_from_meta_data( $meta, $ID = null, $log = true ) {
	global $ewww_debug;
	global $wpdb;
	// may also need to track their attachment ID as well
	$ewww_debug .= "<b>ewww_image_optimizer_resize_from_meta_data()</b><br>";
	$gallery_type = 1;
	$ewww_debug .= "attachment id: $ID<br>";
	if (!metadata_exists('post', $ID, '_wp_attachment_metadata')) {
		$ewww_debug .= "this is a newly uploaded image with no metadata yet<br>";
		$new_image = true;
	} else {
		$ewww_debug .= "this image already has metadata, so it is not new<br>";
		$new_image = false;
	}
	list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $ID );
	// if the attachment has been uploaded via the image store plugin
	if ( 'ims_image' == get_post_type( $ID ) ) {
		$gallery_type = 6;
	}
	// don't do anything else if the attachment path can't be retrieved
	if ( ! is_file( $file_path ) ) {
		$ewww_debug .= "could not retrieve path<br>";
		return $meta;
	}
	$ewww_debug .= "retrieved file path: $file_path<br>";
	// see if this is a new image and Imsanity resized it (which means it could be already optimized)
	if ( ! empty( $new_image ) && function_exists( 'imsanity_get_max_width_height' ) ) {
		list( $maxW, $maxH ) = imsanity_get_max_width_height( IMSANITY_SOURCE_LIBRARY );
		list( $oldW, $oldH ) = getimagesize( $file_path );
		list( $newW, $newH ) = wp_constrain_dimensions( $oldW, $oldH, $maxW, $maxH );
		$path_parts = pathinfo( $file_path );
		$imsanity_path = trailingslashit( $path_parts['dirname'] ) . $path_parts['filename'] . '-' . $newW . 'x' . $newH . '.' . $path_parts['extension'];
		$ewww_debug .= "imsanity path: $imsanity_path<br>";
		$image_size = filesize( $file_path );
		$query = $wpdb->prepare( "SELECT id,path FROM $wpdb->ewwwio_images WHERE path = %s AND image_size = '$image_size'", $imsanity_path );
		$optimized_query = $wpdb->get_results( $query, ARRAY_A );
		if ( ! empty( $optimized_query ) ) {
			foreach ( $optimized_query as $image ) {
				if ( $image['path'] != $imsanity_path ) {
					$ewww_debug .= "{$image['path']} does not match $imsanity_path, continuing our search<br>";
				} else {
					$already_optimized = $image;
				}
			}
		}
		if ( ! empty ( $already_optimized ) ) {
			$ewww_debug .= "updating existing record, path: $file_path, size: " . $image_size . "<br>";
			// store info on the current image for future reference
			$wpdb->update( $wpdb->ewwwio_images,
				array(
					'path' => $file_path,
				),
				array(
					'id' => $already_optimized['id'],
				));
		}
	}
	list($file, $msg, $conv, $original) = ewww_image_optimizer( $file_path, $gallery_type, false, $new_image, true );
	// update the optimization results in the metadata
	$meta['ewww_image_optimizer'] = $msg;
	if ($file === false) {
		return $meta;
	}
	$meta['file'] = str_replace($upload_path, '', $file);
	// if the file was converted
	if ($conv) {
		// update the filename in the metadata
		$new_file = substr($meta['file'], 0, -3);
		// change extension
		$new_ext = substr($file, -3);
		$meta['file'] = $new_file . $new_ext;
		$ewww_debug .= "image was converted<br>";
		// if we don't already have the update attachment filter
		if (FALSE === has_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment'))
			// add the update attachment filter
			add_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2);
		// store the conversion status in the metadata
		$meta['converted'] = 1;
		// store the old filename in the database
		$meta['orig_file'] = $original;
	} else {
		remove_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10);
	}
	// resized versions, so we can continue
	if (isset($meta['sizes']) ) {
		$ewww_debug .= "processing resizes<br>";
		// meta sizes don't contain a path, so we calculate one
		if ($gallery_type === 6) {
			$base_ims_dir = dirname($file_path) . '/_resized/';
		}
		$base_dir = dirname($file_path) . '/';
		// process each resized version
		$processed = array();
		foreach($meta['sizes'] as $size => $data) {
			if ( preg_match('/webp/', $size) ) {
				continue;
			}
			if ($gallery_type === 6) {
				$base_dir = dirname($file_path) . '/';
				$image_path = $base_dir . $data['file'];
				$ims_path = $base_ims_dir . $data['file'];
				if (file_exists($ims_path)) {
					$ewww_debug .= "ims resize already exists, wahoo<br>";
					$ewww_debug .= "ims path: $ims_path<br>";
					$image_size = filesize($ims_path);
					$query = $wpdb->prepare("SELECT id,path FROM $wpdb->ewwwio_images WHERE path = %s AND image_size = '$image_size'", $image_path);
					$optimized_query = $wpdb->get_results($query, ARRAY_A);
					if (!empty($optimized_query)) {
						foreach ( $optimized_query as $image ) {
							if ( $image['path'] != $image_path ) {
								$ewww_debug .= "{$image['path']} does not match $image_path, continuing our search<br>";
							} else {
								$already_optimized = $image;
							}
						}
					}
					if ( ! empty( $already_optimized ) ) {
						$ewww_debug .= "updating existing record, path: $ims_path, size: " . $image_size . "<br>";
						// store info on the current image for future reference
						$wpdb->update( $wpdb->ewwwio_images,
							array(
								'path' => $ims_path,
							),
							array(
								'id' => $already_optimized['id'],
							));
						$base_dir = $base_ims_dir;
					}
				}
			}
			// initialize $dup_size
			$dup_size = false;
			// check through all the sizes we've processed so far
			foreach($processed as $proc => $scan) {
				// if a previous resize had identical dimensions
				if ($scan['height'] == $data['height'] && $scan['width'] == $data['width']) {
					// found a duplicate resize
					$dup_size = true;
					// point this resize at the same image as the previous one
					$meta['sizes'][$size]['file'] = $meta['sizes'][$proc]['file'];
					// and tell the user we didn't do any further optimization
					$meta['sizes'][$size]['ewww_image_optimizer'] = __('No savings', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
			}
			// if this is a unique size
			if (!$dup_size) {
				$resize_path = $base_dir . $data['file'];
				// run the optimization and store the results
				list($optimized_file, $results, $resize_conv, $original) = ewww_image_optimizer( $resize_path, $gallery_type, $conv, $new_image );
				// if the resize was converted, store the result and the original filename in the metadata for later recovery
				if ($resize_conv) {
					// if we don't already have the update attachment filter
					if (FALSE === has_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment')) {
						// add the update attachment filter
						add_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2);
					}
					$meta['sizes'][$size]['converted'] = 1;
					$meta['sizes'][$size]['orig_file'] = str_replace($base_dir, '', $original);
					$ewww_debug .= "original filename: $original<br>";
					$meta['sizes'][$size]['real_orig_file'] = str_replace($base_dir, '', $resize_path);
					$ewww_debug .= "resize path: $resize_path<br>";
				}
				if ($optimized_file !== false) {
					// update the filename
					$meta['sizes'][$size]['file'] = str_replace($base_dir, '', $optimized_file);
				}
				// update the optimization results
				$meta['sizes'][$size]['ewww_image_optimizer'] = $results;
				// optimize retina images, if they exist
				if ( function_exists( 'wr2x_get_retina' ) && $retina_path = wr2x_get_retina( $resize_path ) ) {
					ewww_image_optimizer( $retina_path, 4, false, false );
				}
			}
			// store info on the sizes we've processed, so we can check the list for duplicate sizes
			$processed[$size]['width'] = $data['width'];
			$processed[$size]['height'] = $data['height'];
		}
	}
	
	if ( ! empty( $new_image) ) {
		$meta = ewww_image_optimizer_update_attachment_metadata($meta, $ID);
	}
	if ( ! preg_match( '/' . __( 'Previously Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '/', $meta['ewww_image_optimizer'] ) && class_exists( 'Amazon_S3_And_CloudFront' ) ) {
 		global $as3cf;
		if ( method_exists( $as3cf, 'wp_update_attachment_metadata' ) ) {
			$as3cf->wp_update_attachment_metadata( $meta, $ID );
		} elseif ( method_exists( $as3cf, 'wp_generate_attachment_metadata' ) ) {
			$as3cf->wp_generate_attachment_metadata( $meta, $ID );
		}
		$ewww_debug .= 'uploading to Amazon S3<br>';
	}

	if (class_exists('Cloudinary') && Cloudinary::config_get("api_secret") && ewww_image_optimizer_get_option('ewww_image_optimizer_enable_cloudinary') && !empty($new_image)) {
		try {
			$result = CloudinaryUploader::upload($file,array('use_filename'=>True));
		} catch(Exception $e) {
			$error = $e->getMessage();
		}
		if (!empty($error)) {
			$ewww_debug .= "Cloudinary error: $error<br>";
		} else {
			$ewww_debug .= "successfully uploaded to Cloudinary<br>";
			// register the attachment in the database as a cloudinary attachment
			$old_url = wp_get_attachment_url($ID);
			wp_update_post(array('ID' => $ID,
				'guid' => $result['url']));
			update_attached_file($ID, $result['url']);
			$meta['cloudinary'] = TRUE;
			$errors = array();
			// update the image location for the attachment
			CloudinaryPlugin::update_image_src_all($ID, $result, $old_url, $result["url"], TRUE, $errors);
			if (count($errors) > 0) {
				$ewww_debug .= "Cannot migrate the following posts:<br>" . implode("<br>", $errors);
			}
		}
	}
	if ( $log ) {
		ewww_image_optimizer_debug_log();
	}
	ewwwio_memory( __FUNCTION__ );
	// send back the updated metadata
	return $meta;
}

/**
 * Update the attachment's meta data after being converted 
 */
function ewww_image_optimizer_update_attachment( $meta, $ID ) {
	global $ewww_debug;
	global $wpdb;
	$ewww_debug .= "<b>ewww_image_optimizer_update_attachment()</b><br>";
	// update the file location in the post metadata based on the new path stored in the attachment metadata
	update_attached_file($ID, $meta['file']);
	// retrieve the post information based on the $ID
	$post = get_post($ID);
	// save the previous attachment address
	$old_guid = $post->guid;
	// construct the new guid based on the filename from the attachment metadata
	$guid = dirname($post->guid) . "/" . basename($meta['file']);
	// retrieve any posts that link the image
	$esql = $wpdb->prepare("SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%%%s%%'", $old_guid);
	// while there are posts to process
	$rows = $wpdb->get_results($esql, ARRAY_A);
	foreach ($rows as $row) {
		// replace all occurences of the old guid with the new guid
		$post_content = str_replace($old_guid, $guid, $row['post_content']);
		$ewww_debug .= "replacing $old_guid with $guid in post " . $row['ID'] . '<br>';
		// send the updated content back to the database
		$wpdb->update(
			$wpdb->posts,
			array('post_content' => $post_content),
			array('ID' => $row['ID'])
		);
	}
	if (isset($meta['sizes']) ) {
		// for each resized version
		foreach($meta['sizes'] as $size => $data) {
			// if the resize was converted
			if (isset($data['converted'])) {
				// generate the url for the old image
				if (empty($data['real_orig_file'])) {
					$old_sguid = dirname($post->guid) . "/" . basename($data['orig_file']);
				} else {
					$old_sguid = dirname($post->guid) . "/" . basename($data['real_orig_file']);
					unset ($meta['sizes'][$size]['real_orig_file'] );
				}
				$ewww_debug .= "processing: $size<br>";
				$ewww_debug .= "old guid: $old_sguid <br>";
				// generate the url for the new image
				$sguid = dirname($post->guid) . "/" . basename($data['file']);
				$ewww_debug .= "new guid: $sguid <br>";
				// retrieve any posts that link the resize
				$ersql = $wpdb->prepare("SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%%%s%%'", $old_sguid);
				$ewww_debug .= "using query: $ersql<br>";
				$rows = $wpdb->get_results($ersql, ARRAY_A);
				// while there are posts to process
				foreach ( $rows as $row ) {
					// replace all occurences of the old guid with the new guid
					$post_content = str_replace( $old_sguid, $sguid, $row['post_content'] );
					$ewww_debug .= "replacing $old_sguid with $sguid in post " . $row['ID'] . '<br>';
					// send the updated content back to the database
					$wpdb->update(
						$wpdb->posts,
						array( 'post_content' => $post_content ),
						array( 'ID' => $row['ID'] )
					);
				}
			}
		}
	}
	// if the new image is a JPG
	if ( preg_match( '/.jpg$/i', basename( $meta['file'] ) ) ) {
		// set the mimetype to JPG
		$mime = 'image/jpg';
	}
	// if the new image is a PNG
	if ( preg_match( '/.png$/i', basename( $meta['file'] ) ) ) {
		// set the mimetype to PNG
		$mime = 'image/png';
	}
	if ( preg_match( '/.gif$/i', basename( $meta['file'] ) ) ) {
		// set the mimetype to GIF
		$mime = 'image/gif';
	}
	// update the attachment post with the new mimetype and guid
	wp_update_post( array('ID' => $ID,
			      'post_mime_type' => $mime,
			      'guid' => $guid ) );
	ewww_image_optimizer_debug_log();
	ewwwio_memory( __FUNCTION__ );
	return $meta;
}

// retrieves path of an attachment via the $id and the $meta
// returns a $file_path and $upload_path
function ewww_image_optimizer_attachment_path( $meta, $ID ) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_attachment_path()</b><br>";
	// retrieve the location of the wordpress upload folder
	$upload_dir = wp_upload_dir();
	// retrieve the path of the upload folder
	$upload_path = trailingslashit( $upload_dir['basedir'] );
	// get the filepath
	$file_path = get_attached_file( $ID );
	$ewww_debug .= "WP thinks the file is at: $file_path<br>";
	if ( is_file( $file_path ) ) {
		return array( $file_path, $upload_path );
	}
	if ( 'ims_image' == get_post_type( $ID ) && !empty( $meta['file'] ) ) {
		$ims_options = ewww_image_optimizer_get_option( 'ims_front_options' );
		$ims_path = $ims_options['galleriespath'];
		if ( is_dir( $file_path ) ) {
			$upload_path = $file_path;
			$file_path = $meta['file'];
			// generate the absolute path
			$file_path =  $upload_path . $file_path;
		} elseif ( is_file( $meta['file'] ) ) {
			$file_path = $meta['file'];
			$upload_path = '';
		} else {
			$upload_path = WP_CONTENT_DIR;
			if ( strpos( $meta['file'], $ims_path ) === false ) {
				$upload_path = trailingslashit( WP_CONTENT_DIR );
			}
			$file_path = $upload_path . $meta['file'];
		}
		return array( $file_path, $upload_path );
	}
	if ( ! empty( $meta['file'] ) ) {
		$file_path = $meta['file'];
		if ( is_file( $file_path ) ) {
			return array( $file_path, $upload_path );
		}
		$file_path = $upload_path . $file_path;
		if ( is_file( $file_path ) ) {
			return array( $file_path, $upload_path );
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return array( '', $upload_path );
}

// takes a human-readable size, and generates an approximate byte-size
function ewww_image_optimizer_size_unformat( $formatted ) {
	$size_parts = explode( '&nbsp;', $formatted );
	switch ( $size_parts[1] ) {
		case 'B':
			return intval( $size_parts[0] );
		case 'kB':
			return intval( $size_parts[0] * 1024 );
		case 'MB':
			return intval( $size_parts[0] * 1048576 );
		case 'GB':
			return intval( $size_parts[0] * 1073741824 );
		case 'TB':
			return intval( $size_parts[0] * 1099511627776 );
		default:
			return 0;
	}
}

// generate a unique filename for a converted image
function ewww_image_optimizer_unique_filename( $file, $fileext ) {
	// strip the file extension
	$filename = preg_replace( '/\.\w+$/', '', $file );
	// set the increment to 1 (we always rename converted files with an increment)
	$filenum = 1;
	// while a file exists with the current increment
	while ( file_exists( $filename . '-' . $filenum . $fileext ) ) {
		// increment the increment...
		$filenum++;
	}
	// all done, let's reconstruct the filename
	ewwwio_memory( __FUNCTION__ );
	return array( $filename . '-' . $filenum . $fileext, $filenum );
}

/**
 * Check the submitted PNG to see if it has transparency
 */
function ewww_image_optimizer_png_alpha( $filename ) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_png_alpha()</b><br>";
	// determine what color type is stored in the file
	$color_type = ord(@file_get_contents($filename, NULL, NULL, 25, 1));
	$ewww_debug .= "color type: $color_type<br>";
	// if it is set to RGB alpha or Grayscale alpha
	if ($color_type == 4 || $color_type == 6) {
		$ewww_debug .= "transparency found<br>";
		return true;
	} elseif ($color_type == 3 && ewww_image_optimizer_gd_support()) {
		$image = imagecreatefrompng($filename);
		if (imagecolortransparent($image) >= 0) {
			$ewww_debug .= "transparency found<br>";
			return true;
		}
		list($width, $height) = getimagesize($filename);
		$ewww_debug .= "image dimensions: $width x $height<br>";
		$ewww_debug .= "preparing to scan image<br>";
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$color = imagecolorat($image, $x, $y);
				$rgb = imagecolorsforindex($image, $color);
				if ($rgb['alpha'] > 0) {
					$ewww_debug .= "transparency found<br>";
					return true;
				}
			}
		}
	}
	$ewww_debug .= "no transparency<br>";
	ewwwio_memory( __FUNCTION__ );
	return false;
}

/**
 * Check the submitted GIF to see if it is animated
 */
function ewww_image_optimizer_is_animated($filename) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_is_animated()</b><br>";
	// if we can't open the file in read-only buffered mode
	if(!($fh = @fopen($filename, 'rb'))) {
		return false;
	}
	// initialize $count
	$count = 0;
   
	// We read through the file til we reach the end of the file, or we've found
	// at least 2 frame headers
	while(!feof($fh) && $count < 2) {
		$chunk = fread($fh, 1024 * 100); //read 100kb at a time
		$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
	}
	fclose($fh);
	// return TRUE if there was more than one frame, or FALSE if there was only one
	ewwwio_memory( __FUNCTION__ );
	return $count > 1;
}

/**
 * Print column header for optimizer results in the media library using
 * the `manage_media_columns` hook.
 */
function ewww_image_optimizer_columns($defaults) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_optimizer_columns()</b><br>";
	$defaults['ewww-image-optimizer'] = __( 'Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	ewwwio_memory( __FUNCTION__ );
	return $defaults;
}

/**
 * Print column data for optimizer results in the media library using
 * the `manage_media_custom_column` hook.
 */
function ewww_image_optimizer_custom_column($column_name, $id) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_custom_column()</b><br>";
	// once we get to the EWWW IO custom column
	if ($column_name == 'ewww-image-optimizer') {
		// retrieve the metadata
		$meta = wp_get_attachment_metadata($id);
		if (ewww_image_optimizer_get_option('ewww_image_optimizer_debug')) {
			$print_meta = print_r($meta, TRUE);
			$print_meta = preg_replace(array('/ /', '/\n+/'), array('&nbsp;', '<br />'), $print_meta);
			echo '<div style="background-color:#ffff99;font-size: 10px;padding: 10px;margin:-10px -10px 10px;line-height: 1.1em">' . $print_meta . '</div>';
		}
		if(!empty($meta['cloudinary'])) {
			_e('Cloudinary image', EWWW_IMAGE_OPTIMIZER_DOMAIN);
			return;
		}
		// if the filepath isn't set in the metadata
		if(empty($meta['file'])){
			if (isset($meta['file'])) {
				unset($meta['file']);
				if (strpos($meta['ewww_image_optimizer'], 'Could not find') === 0) {
					unset($meta['ewww_image_optimizer']);
				}
				wp_update_attachment_metadata($id, $meta);
			}
		}
		list($file_path, $upload_path) = ewww_image_optimizer_attachment_path($meta, $id);
		// if the file does not exist
		if (empty($file_path)) {
			_e('Could not retrieve file path.', EWWW_IMAGE_OPTIMIZER_DOMAIN);
			return;
		}
		$msg = '';
		$convert_desc = '';
		$convert_link = '';
		// retrieve the mimetype of the attachment
		$type = ewww_image_optimizer_mimetype($file_path, 'i');
		// get a human readable filesize
		$file_size = size_format(filesize($file_path), 2);
		$file_size = preg_replace('/\.00 B /', ' B', $file_size);
		// run the appropriate code based on the mimetype
		switch($type) {
			case 'image/jpeg':
				// if jpegtran is missing, tell them that
				if(!EWWW_IMAGE_OPTIMIZER_JPEGTRAN && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
					$valid = false;
					$msg = '<br>' . sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>jpegtran</em>');
				} else {
					$convert_link = __('JPG to PNG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$class_type = 'jpg';
					$convert_desc = __('WARNING: Removes metadata. Requires GD or ImageMagick. PNG is generally much better than JPG for logos and other images with a limited range of colors.', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				break; 
			case 'image/png':
				// if pngout and optipng are missing, tell the user
				if(!EWWW_IMAGE_OPTIMIZER_PNGOUT && !EWWW_IMAGE_OPTIMIZER_OPTIPNG && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png')) {
					$valid = false;
					$msg = '<br>' . sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>optipng/pngout</em>');
				} else {
					$convert_link = __('PNG to JPG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$class_type = 'png';
					$convert_desc = __('WARNING: This is not a lossless conversion and requires GD or ImageMagick. JPG is much better than PNG for photographic use because it compresses the image and discards data. Transparent images will only be converted if a background color has been set.', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				break;
			case 'image/gif':
				// if gifsicle is missing, tell the user
				if(!EWWW_IMAGE_OPTIMIZER_GIFSICLE && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif')) {
					$valid = false;
					$msg = '<br>' . sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>gifsicle</em>');
				} else {
					$convert_link = __('GIF to PNG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$class_type = 'gif';
					$convert_desc = __('PNG is generally better than GIF, but does not support animation. Animated images will not be converted.', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				break;
			default:
				// not a supported mimetype
				_e('Unsupported file type', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				return;
		}
		// if the optimizer metadata exists
		if ( ! empty($meta['ewww_image_optimizer']) ) {
			// output the optimizer results
			echo $meta['ewww_image_optimizer'];
			// output the filesize
			echo "<br>" . sprintf(__('Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), $file_size);
			if ( empty( $msg ) && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				// output a link to re-optimize manually
				printf("<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_force=1&amp;ewww_attachment_ID=%d\">%s</a>",
					$id,
					__('Re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN));
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_convert_links') && 'ims_image' != get_post_type($id)) {
					echo " | <a class='ewww-convert' title='$convert_desc' href='admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_attachment_ID=$id&amp;ewww_convert=1&amp;ewww_force=1'>$convert_link</a>";
				}
			} else {
				echo $msg;
			}
			$restorable = false;
			if (!empty($meta['converted'])) {
				if (!empty($meta['orig_file']) && file_exists($meta['orig_file'])) {
					$restorable = true;
				}
			}
			if (isset($meta['sizes']) ) {
				// meta sizes don't contain a path, so we calculate one
				$base_dir = dirname($file_path) . '/';
				foreach($meta['sizes'] as $size => $data) {
					if (!empty($data['converted'])) {
						if (!empty($data['orig_file']) && file_exists($base_dir . $data['orig_file'])) {
							$restorable = true;
						}
					}		
				}
			}
			if ( $restorable && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				printf( "<br><a href=\"admin.php?action=ewww_image_optimizer_manual_restore&amp;ewww_attachment_ID=%d\">%s</a>",
					$id,
					__( 'Restore original', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
			}

			// link to webp upgrade script
			$oldwebpfile = preg_replace('/\.\w+$/', '.webp', $file_path);
			if ( file_exists( $oldwebpfile ) && current_user_can( apply_filters( 'ewww_image_optimizer_admin_permissions', '' ) ) ) {
				echo "<br><a href='options.php?page=ewww-image-optimizer-webp-migrate'>Run WebP upgrade</a>";
			}

			// determine filepath for webp
			$webpfile = $file_path . '.webp';
			if ( file_exists( $webpfile ) ) {
				$webpurl = wp_get_attachment_url( $id ) . '.webp';
				// get a human readable filesize
				$webp_size = size_format( filesize( $webpfile ), 2 );
				$webp_size = preg_replace( '/\.00 B /', ' B', $webp_size );
				echo "<br>WebP: <a href='$webpurl'>$webp_size</a>";
			}
		} else {
			// otherwise, this must be an image we haven't processed
			_e( 'Not processed', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			// tell them the filesize
			echo "<br>" . sprintf( __( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size );
			if ( empty( $msg ) && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				// and give the user the option to optimize the image right now
				printf( "<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_attachment_ID=%d\">%s</a>", $id, __( 'Optimize now!', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_convert_links' ) && 'ims_image' != get_post_type( $id ) ) {
					echo " | <a class='ewww-convert' title='$convert_desc' href='admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_attachment_ID=$id&amp;ewww_convert=1&amp;ewww_force=1'>$convert_link</a>";
				}
			} else {
				echo $msg;
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
}

function ewww_image_optimizer_load_admin_js() {
	add_action( 'admin_print_footer_scripts', 'ewww_image_optimizer_add_bulk_actions_via_javascript' ); 
}

// Borrowed from http://www.viper007bond.com/wordpress-plugins/regenerate-thumbnails/
// adds a bulk optimize action to the drop-down on the media library page
function ewww_image_optimizer_add_bulk_actions_via_javascript() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_add_bulk_actions_via_javascript()</b><br>";
	if ( ! current_user_can( apply_filters( 'ewww_image_optimizer_bulk_permissions', '' ) ) ) {
		return;
	}
?>
	<script type="text/javascript"> 
		jQuery(document).ready(function($){ 
			$('select[name^="action"] option:last-child').before('<option value="bulk_optimize"><?php _e('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></option>');
			$('.ewww-convert').tooltip();
			//$('.bulk-select select option:last-child').before('<option value="bulk_optimize"><?php _e('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></option>');
		}); 
	</script>
<?php } 

// Handles the bulk actions POST 
// Borrowed from http://www.viper007bond.com/wordpress-plugins/regenerate-thumbnails/ 
function ewww_image_optimizer_bulk_action_handler() { 
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_bulk_action_handler()</b><br>";
	// if the requested action is blank, or not a bulk_optimize, do nothing
	if ( empty( $_REQUEST['action'] ) || ( 'bulk_optimize' != $_REQUEST['action'] && 'bulk_optimize' != $_REQUEST['action2'] ) ) {
		return;
	}
	// if there is no media to optimize, do nothing
	if ( empty( $_REQUEST['media'] ) || ! is_array( $_REQUEST['media'] ) ) {
		return; 
	}
	// check the referring page
	check_admin_referer( 'bulk-media' ); 
	// prep the attachment IDs for optimization
	$ids = implode( ',', array_map( 'intval', $_REQUEST['media'] ) ); 
	wp_redirect(add_query_arg(array('page' => 'ewww-image-optimizer-bulk', '_wpnonce' => wp_create_nonce('ewww-image-optimizer-bulk'), 'goback' => 1, 'ids' => $ids), admin_url('upload.php'))); 
	ewwwio_memory( __FUNCTION__ );
	exit(); 
}

// display a page of unprocessed images from Media library
function ewww_image_optimizer_display_unoptimized_media() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_display_unoptimized_media()</b><br>";
	$attachments = ewww_image_optimizer_count_optimized ( 'media', true );
	echo "<div class='wrap'><h3>" . __('Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</h3>";
	printf( '<p>' . __( 'We have %d images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</p>', count( $attachments ) );
	if ( count( $attachments ) != 0 ) {
		sort($attachments, SORT_NUMERIC);
		$image_string = implode(',', $attachments);
		echo '<form method="post" action="upload.php?page=ewww-image-optimizer-bulk">'
			. "<input type='hidden' name='ids' value='$image_string' />"
			. '<input type="submit" class="button-secondary action" value="' . __('Optimize All Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '" />'
			. '</form>';
		if ( count( $attachments ) < 500 ) {
			sort($attachments, SORT_NUMERIC);
			$image_string = implode(',', $attachments);
			echo '<table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>ID</th><th>&nbsp;</th><th>' . __('Title', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . __('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th></tr></thead>';
			$alternate = true;
			foreach ($attachments as $ID) {
				$image_name = get_the_title($ID);
?>				<tr<?php if($alternate) echo " class='alternate'"; ?>><td><?php echo $ID; ?></td>
<?php				echo "<td style='width:80px' class='column-icon'>" . wp_get_attachment_image( $ID, 'thumbnail' ) . "</td>";
				echo "<td class='title'>$image_name</td>";
				echo "<td>";
				ewww_image_optimizer_custom_column('ewww-image-optimizer', $ID);
				echo "</td></tr>";
				$alternate = !$alternate;
			}
			echo '</table>';
		} else {
			echo '<p>' . __( 'There are too many images to display.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</p>'; 
		}
	}
	echo '</div>';
	return;	
}

// retrieve an option: use 'site' setting if plugin is network activated, otherwise use 'blog' setting
function ewww_image_optimizer_get_option ($option_name) {
	if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(plugin_basename(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE))) {
		$option_value = get_site_option($option_name);
	} else {
		$option_value = get_option($option_name);
	}
	return $option_value;
}

// set an option: use 'site' setting if plugin is network activated, otherwise use 'blog' setting
function ewww_image_optimizer_set_option ($option_name, $option_value) {
	if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(plugin_basename(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE))) {
		$success = update_site_option($option_name, $option_value);
	} else {
		$success = update_option($option_name, $option_value);
	}
	return $success;
}

function ewww_image_optimizer_settings_script($hook) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_settings_script()</b><br>";
	global $wpdb;
	// make sure we are being called from the bulk optimization page
	if (strpos($hook,'settings_page_ewww-image-optimizer') !== 0) {
		return;
	}
/*	if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL)) {
		$savings_todo = 0;
		if (function_exists('wp_get_sites')) {
			add_filter('wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0);
			$blogs = wp_get_sites(array(
				'network_id' => $wpdb->siteid,
				'limit' => 10000
			));
			remove_filter('wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0);
		} else {
			$query = "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";
			$blogs = $wpdb->get_results($query, ARRAY_A);
		}
		foreach ($blogs as $blog) {
			switch_to_blog($blog['blog_id']);
			$savings_todo += $wpdb->get_var("SELECT COUNT(id) FROM $wpdb->ewwwio_images");
		}
		restore_current_blog();
	} else {
		$savings_todo = $wpdb->get_var("SELECT COUNT(id) FROM $wpdb->ewwwio_images");
	}*/
	// require the files that do the bulk processing 
		
//	$ewww_debug .= "images to check for savings: $savings_todo<br>";
	wp_enqueue_script( 'ewwwbulkscript', plugins_url( '/eio.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'postbox' );
	wp_enqueue_script( 'dashboard' );
	wp_localize_script( 'ewwwbulkscript', 'ewww_vars', array(
			'_wpnonce' => wp_create_nonce( 'ewww-image-optimizer-settings' ),
//			'savings_todo' => $savings_todo,
		)
	);
	ewwwio_memory( __FUNCTION__ );
	return;
}

function ewww_image_optimizer_savings() {
	global $wpdb;
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_savings()</b><br>";
	if ( function_exists('is_plugin_active_for_network') && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		$ewww_debug .= 'querying savings for multi-site<br>';
		if (function_exists('wp_get_sites')) {
			$ewww_debug .= 'retrieving list of sites the easy way<br>';
			add_filter('wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0);
			$blogs = wp_get_sites(array(
				'network_id' => $wpdb->siteid,
				'limit' => 10000
			));
			remove_filter('wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0);
		} else {
			$ewww_debug .= 'retrieving list of sites the hard way<br>';
			$query = "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";
			$blogs = $wpdb->get_results($query, ARRAY_A);
		}
		$total_savings = 0;
		foreach ($blogs as $blog) {
			switch_to_blog($blog['blog_id']);
			$ewww_debug .= "getting savings for site: {$blog['blog_id']}<br>";
			$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'ewwwio_images WHERE image_size > orig_size' );
			$total_query = "SELECT SUM(orig_size-image_size) FROM $wpdb->ewwwio_images";
			$ewww_debug .= "query to be performed: $total_query<br>";
			$savings = $wpdb->get_var($total_query);
			$ewww_debug .= "savings found: $savings<br>";
			$total_savings += $savings;
		}
		restore_current_blog();
	} else {
		$ewww_debug .= 'querying savings for single site<br>';
		$total_savings = 0;
		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'ewwwio_images WHERE image_size > orig_size' );
		$total_query = "SELECT SUM(orig_size-image_size) FROM $wpdb->ewwwio_images";
		$ewww_debug .= "query to be performed: $total_query<br>";
		$total_savings = $wpdb->get_var($total_query);
		$ewww_debug .= "savings found: $total_savings<br>";
	}
	return $total_savings;
}

function ewww_image_optimizer_webp_rewrite() {
	// verify that the user is properly authorized
	if (!wp_verify_nonce($_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-settings')) {
		wp_die(__('Cheatin&#8217; eh?', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	}
	if ( $ewww_rules = ewww_image_optimizer_webp_rewrite_verify() ) {
	if ( insert_with_markers( get_home_path() . '.htaccess', 'EWWWIO', $ewww_rules ) && ! ewww_image_optimizer_webp_rewrite_verify() ) {
		_e('Insertion successful', EWWW_IMAGE_OPTIMIZER_DOMAIN);
	} else {
		_e('Insertion failed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
	}
	}
	die();
}

// if rules are present, stay silent, otherwise, give us some rules to insert!
function ewww_image_optimizer_webp_rewrite_verify() {
	$current_rules = extract_from_markers( get_home_path() . '.htaccess', 'EWWWIO' ) ;
	$ewww_rules = array(
		"<IfModule mod_rewrite.c>",
		"RewriteEngine On",
		"RewriteCond %{HTTP_ACCEPT} image/webp",
		"RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$",
		"RewriteCond %{REQUEST_FILENAME}.webp -f",
		"RewriteRule (.+)\.(jpe?g|png)$ %{REQUEST_FILENAME}.webp [T=image/webp,E=accept:1]",
		"</IfModule>",
		"<IfModule mod_headers.c>",
		"Header append Vary Accept env=REDIRECT_accept",
		"</IfModule>",
		"AddType image/webp .webp",
	);
	if ( array_diff( $ewww_rules, $current_rules ) ) {
	ewwwio_memory( __FUNCTION__ );
		return $ewww_rules;
	} else {
	ewwwio_memory( __FUNCTION__ );
		return;
	}
}

// displays the EWWW IO options and provides one-click install for the optimizer utilities
function ewww_image_optimizer_options () {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_options()</b><br>";
	$output = array();
	if (isset($_REQUEST['ewww_pngout'])) {
		if ($_REQUEST['ewww_pngout'] == 'success') {
			$output[] = "<div id='ewww-image-optimizer-pngout-success' class='updated fade'>\n";
			$output[] = '<p>' . __('Pngout was successfully installed, check the Plugin Status area for version information.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n";
			$output[] = "</div>\n";
		}
		if ($_REQUEST['ewww_pngout'] == 'failed') {
			$output[] = "<div id='ewww-image-optimizer-pngout-failure' class='error'>\n";
			$output[] = '<p>' . sprintf(__('Pngout was not installed: %1$s. Make sure this folder is writable: %2$s', EWWW_IMAGE_OPTIMIZER_DOMAIN), sanitize_text_field( $_REQUEST['ewww_error'] ), EWWW_IMAGE_OPTIMIZER_TOOL_PATH) . "</p>\n";
			$output[] = "</div>\n";
		}
	} 
	$output[] = "<script type='text/javascript'>\n" .
		'jQuery(document).ready(function($) {$(".fade").fadeTo(5000,1).fadeOut(3000);$(".updated").fadeTo(5000,1).fadeOut(3000);});' . "\n" .
		"</script>\n";
	$output[] = "<style>\n" .
		".ewww-tab a { font-size: 15px; font-weight: 700; color: #555; text-decoration: none; line-height: 36px; padding: 0 10px; }\n" .
		".ewww-tab a:hover { color: #464646; }\n" .
		".ewww-tab { margin: 0 0 0 5px; padding: 0px; border-width: 1px 1px 1px; border-style: solid solid none; border-image: none; border-color: #ccc; display: inline-block; background-color: #e4e4e4 }\n" .
		".ewww-tab:hover { background-color: #fff }\n" .
		".ewww-selected { background-color: #f1f1f1; margin-bottom: -1px; border-bottom: 1px solid #f1f1f1 }\n" .
		".ewww-selected a { color: #000; }\n" .
		".ewww-selected:hover { background-color: #f1f1f1; }\n" .
		".ewww-tab-nav { list-style: none; margin: 10px 0 0; padding-left: 5px; border-bottom: 1px solid #ccc; }\n" .
//		".ewww-name { font-size: 1.7em; line-height: 46px; color: #fff; background: #1e4378 url(" . plugins_url('smashing-icon.png', __FILE__) . ") no-repeat left; margin: 0 0 0; padding: 0 1em 0 80px; }\n" .
//		".ewww-name-spacer { background-color: #1e4378; margin: 0 0 0 -20px; padding-left: 10px; }\n" .
	"</style>\n";
	$output[] = "<div class='wrap'>\n";
	$output[] = "<h2>EWWW Image Optimizer</h2>\n";
	$output[] = "<div id='ewww-container-left' style='float: left; margin-right: 225px;'>\n";
	$output[] = "<p><a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/'>" . __('Plugin Home Page', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a> | " .
		"<a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" .  __('Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a> | " .
		"<a href='https://wordpress.org/support/plugin/ewww-image-optimizer'>" . __('Plugin Support', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a> | " .
		"<a href='https://ewww.io/status/'>" . __('Cloud Status', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p>\n";
		if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL)) {
			$bulk_link = __('Media Library') . ' -> ' . __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		} else {
			$bulk_link = '<a href="upload.php?page=ewww-image-optimizer-bulk">' . __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a>';
		}
	$output[] = "<p>" . sprintf(__('New images uploaded to the Media Library will be optimized automatically. If you have existing images you would like to optimize, you can use the %s tool.', EWWW_IMAGE_OPTIMIZER_DOMAIN), $bulk_link) . "</p>\n";
	if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
		$collapsed = '';
	} else {
		$collapsed = "$('#ewww-status').toggleClass('closed');\n";
	}
	$output[] = "<div id='ewww-widgets' class='metabox-holder'><div class='meta-box-sortables'><div id='ewww-status' class='postbox'>\n" .
		"<div class='handlediv' title='" . esc_attr__('Click to toggle') . "'><br></div>" .
		"<h3 class='hndle'>" . __('Plugin Status', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&emsp;" .
			"<span id='ewww-status-ok' style='display: none; color: green;'>" . __('All Clear', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>" . 
			"<span id='ewww-status-attention' style='color: red;'>" . __('Requires Attention', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>"  .
		"</h3>\n" .
			"<div class='inside'>" .
			"<b>" . __('Total Savings:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> <span id='ewww-total-savings'>" . size_format( ewww_image_optimizer_savings(), 2 ) . "</span><br>";
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key')) {
				$output[] = '<p><b>' . __('Cloud optimization API Key', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ":</b> ";
				$verify_cloud = ewww_image_optimizer_cloud_verify(false); 
				if (preg_match('/great/', $verify_cloud)) {
					$output[] = '<span style="color: green">' . __('Verified,', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </span>' . ewww_image_optimizer_cloud_quota();
				} elseif (preg_match('/exceeded/', $verify_cloud)) { 
					$output[] = '<span style="color: orange">' . __('Verified,', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </span>' . ewww_image_optimizer_cloud_quota();
				} else { 
					$output[] = '<span style="color: red">' . __('Not Verified', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
				}
				$output[] = "</p>\n";
			}
			$collapsible = true;
			if ( ! EWWW_IMAGE_OPTIMIZER_CLOUD ) {
			/*	$output[] = "<div id='ewww-status-expand' style='display: none;'><a href='#'>" . __('Expand', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></div>\n" .
					"<div id='ewww-status-collapse' style='display: none;'><a href='#'>" . __('Collapse', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></div>\n" .
					"<div id='ewww-collapsible-status'>\n";*/
//				$output[] = "<div id='ewww-collapsible-status'>\n";
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_skip_bundle') && !EWWW_IMAGE_OPTIMIZER_CLOUD && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<p>" . __('If updated versions are available below you may either download the newer versions and install them yourself, or uncheck "Use System Paths" and use the bundled tools.', EWWW_IMAGE_OPTIMIZER_DOMAIN)  . "<br />\n" .
					"<i>*" . __('Updates are optional, but may contain increased optimization or security patches', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</i></p>\n";
			} elseif (!EWWW_IMAGE_OPTIMIZER_CLOUD && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<p>" . sprintf(__('If updated versions are available below, you may need to enable write permission on the %s folder to use the automatic installs.', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<i>' . EWWW_IMAGE_OPTIMIZER_TOOL_PATH . '</i>') . "<br />\n" .
					"<i>*" . __('Updates are optional, but may contain increased optimization or security patches', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</i></p>\n";
			}
			if (!EWWW_IMAGE_OPTIMIZER_CLOUD && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				list ($jpegtran_src, $optipng_src, $gifsicle_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst) = ewww_image_optimizer_install_paths();
			}
			$output[] = "<p>\n";
			if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_jpegtran') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')  && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>jpegtran:</b> ";
				$jpegtran_installed = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_JPEGTRAN, 'j');
				if (!empty($jpegtran_installed)) {
					$output[] = '<span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . __('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $jpegtran_installed . "<br />\n"; 
				} else { 
					$output[] = '<span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>optipng:</b> ";
				$optipng_version = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_OPTIPNG, 'o');
				if (!empty($optipng_version)) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . __('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $optipng_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_gifsicle') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif') && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>gifsicle:</b> ";
				$gifsicle_version = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_GIFSICLE, 'g');
				if (!empty($gifsicle_version) && preg_match('/LCDF Gifsicle/', $gifsicle_version)) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . __('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $gifsicle_version . "<br />\n";
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>pngout:</b> ";
				$pngout_version = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_PNGOUT, 'p');
				if (!empty($pngout_version) && (preg_match('/PNGOUT/', $pngout_version))) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . __('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . preg_replace('/PNGOUT \[.*\)\s*?/', '', $pngout_version) . "<br />\n";
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;<b>' . __('Install', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' <a href="admin.php?action=ewww_image_optimizer_install_pngout">' . __('automatically', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a> | <a href="http://advsys.net/ken/utils.htm">' . __('manually', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a></b> - ' . __('Pngout is free closed-source software that can produce drastically reduced filesizes for PNGs, but can be very time consuming to process images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br />\n"; 
					$collapsible = false;
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>pngquant:</b> ";
				$pngquant_version = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_PNGQUANT, 'q');
				if (!empty($pngquant_version)) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . __('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $pngquant_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_webp') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg') && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>webp:</b> ";
				$webp_version = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_WEBP, 'w');
				if (!empty($webp_version)) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . __('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $webp_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if (!EWWW_IMAGE_OPTIMIZER_CLOUD && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				if (ewww_image_optimizer_safemode_check()) {
					$output[] = 'safe mode: <span style="color: red; font-weight: bolder">' . __('On', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
					$collapsible = false;
				} else {
					$output[] = 'safe mode: <span style="color: green; font-weight: bolder">' . __('Off', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				}
				if (ewww_image_optimizer_exec_check()) {
					$output[] = 'exec(): <span style="color: red; font-weight: bolder">' . __('Disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
					$collapsible = false;
				} else {
					$output[] = 'exec(): <span style="color: green; font-weight: bolder">' . __('Enabled', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				}
				$output[] = "<br />\n";
				$output[] = sprintf(__("%s only need one, used for conversion, not optimization", EWWW_IMAGE_OPTIMIZER_DOMAIN), '<b>' . __('Graphics libraries', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b> - ');
				$output[] = '<br>';
				$toolkit_found = false;
				if (ewww_image_optimizer_gd_support()) {
					$output[] = 'GD: <span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$toolkit_found = true;
				} else {
					$output[] = 'GD: <span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				} 
				$output[] = '</span>&emsp;&emsp;' .
					"Imagemagick 'convert':";
				if (ewww_image_optimizer_find_binary('convert', 'i')) { 
					$output[] = '<span style="color: green; font-weight: bolder"> ' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
					$toolkit_found = true;
				} else { 
					$output[] = '<span style="color: red; font-weight: bolder"> ' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
				}
				if ( ! $toolkit_found && ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_to_jpg' ) || ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_to_png' ) ) ) {
					$collapsible = false;
				}
				$output[] = "<br />\n";
			}
			$output[] = '<b>' . __('Only need one of these:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </b><br>';
			// initialize this variable to check for the 'file' command if we don't have any php libraries we can use
			$file_command_check = true;
			if (function_exists('finfo_file')) {
				$output[] = 'finfo: <span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				$file_command_check = false;
			} else {
				$output[] = 'finfo: <span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
			}
			if (function_exists('getimagesize')) {
				$output[] = 'getimagesize(): <span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				if ( EWWW_IMAGE_OPTIMIZER_CLOUD || EWWW_IMAGE_OPTIMIZER_NOEXEC || PHP_OS == 'WINNT' ) {
					$file_command_check = false;
				}
			} else {
				$output[] = 'getimagesize(): <span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
			}
			if (function_exists('mime_content_type')) {
				$output[] = 'mime_content_type(): <span style="color: green; font-weight: bolder">' . __('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
				$file_command_check = false;
			} else {
				$output[] = 'mime_content_type(): <span style="color: red; font-weight: bolder">' . __('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
			}
			if (PHP_OS != 'WINNT' && !EWWW_IMAGE_OPTIMIZER_CLOUD && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				if ($file_command_check && !ewww_image_optimizer_find_binary('file', 'f')) {
					$output[] = '<span style="color: red; font-weight: bolder">file ' . __('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />';
					$collapsible = false;
				}
				if (!ewww_image_optimizer_find_binary('nice', 'n')) {
					$output[] = '<span style="color: orange; font-weight: bolder">nice ' . __('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' (' . __('not required', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ')</span><br />';
				}
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && PHP_OS != 'SunOS' && !ewww_image_optimizer_find_binary('tar', 't')) {
					$output[] = '<span style="color: red; font-weight: bolder">tar ' . __('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' (' . __('required for automatic pngout installer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ')</span><br />';
					$collapsible = false;
				}
			} elseif ($file_command_check) {
				$collapsible = false;
			}
			if ( ! EWWW_IMAGE_OPTIMIZER_CLOUD ) {
//				$output[] = '</div><!-- end collapsible -->';
			}
			$output[] = '</div><!-- end .inside -->';
			if ( $collapsible ) {
				$output[] = "<script type='text/javascript'>\n" .
					"jQuery(document).ready(function($) {\n" .
//					"$('#ewww-collapsible-status').hide();\n" .
					$collapsed .
//					"$('#ewww-status').toggleClass('closed');\n" .
//					"$('#ewww-status-expand').show();\n" .
					"$('#ewww-status-attention').hide();\n" .
					"$('#ewww-status-ok').show();\n" .
					"});\n" .
					"</script>\n";
			}
			$output[] = "</div></div></div>\n";
	$output[] = "<ul class='ewww-tab-nav'>\n" .
		"<li class='ewww-tab ewww-cloud-nav'><span class='ewww-tab-hidden'><a class='ewww-cloud-nav' href='#'>" . __('Cloud Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-general-nav'><span class='ewww-tab-hidden'><a class='ewww-general-nav' href='#'>" . __('Basic Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-optimization-nav'><span class='ewww-tab-hidden'><a class='ewww-optimization-nav' href='#'>" .  __('Advanced Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-conversion-nav'><span class='ewww-tab-hidden'><a class='ewww-conversion-nav' href='#'>" . __('Conversion Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
	"</ul>\n";
			if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL)) {
				$output[] = "<form method='post' action=''>\n";
			} else {
				$output[] = "<form method='post' action='options.php'>\n";
			}
			$output[] = "<input type='hidden' name='option_page' value='ewww_image_optimizer_options' />\n";
		        $output[] = "<input type='hidden' name='action' value='update' />\n";
		        $output[] = wp_nonce_field( "ewww_image_optimizer_options-options", '_wpnonce', true, false ) . "\n";
			$output[] = "<div id='ewww-cloud-settings'>\n";
			$output[] = "<p>" . __('If exec() is disabled for security reasons (and enabling it is not an option), or you would like to offload image optimization to a third-party server, you may purchase an API key for our cloud optimization service. The API key should be entered below, and cloud optimization must be enabled for each image format individually.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . __('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p>\n";
			$output[] = "<table class='form-table'>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_key'>" . __('Cloud optimization API Key', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_cloud_key' name='ewww_image_optimizer_cloud_key' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key') . "' size='32' /> " . __('API Key will be validated when you save your settings.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . __('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_jpg'>" . __('JPG cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_cloud_jpg' name='ewww_image_optimizer_cloud_jpg' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg') == TRUE ? "checked='true'" : "" ) . " />&emsp;&emsp;" . __('Use the Basic Settings tab to enable lossy compression with JPEGmini.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_png'>" . __('PNG cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_cloud_png' name='ewww_image_optimizer_cloud_png' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') == TRUE ? "checked='true'" : "" ) . " /></td></tr>";
					$output[] = "<tr><td>&nbsp;</td><td><input type='checkbox' id='ewww_image_optimizer_cloud_png_compress' name='ewww_image_optimizer_cloud_png_compress' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png_compress') == TRUE ? "checked='true'" : "" ) . " /><label for='ewww_image_optimizer_cloud_png_compress'>" . __('extra PNG compression (slower)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_gif'>" . __('GIF cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_cloud_gif' name='ewww_image_optimizer_cloud_gif' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_lossy_fast'>" . __('Faster lossy optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_lossy_fast' name='ewww_image_optimizer_lossy_fast' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_fast') == TRUE ? "checked='true'" : "" ) . " /> " . __('Speed up the lossy operations by performing less compression.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
			$output[] = "</table>\n</div>\n";
			$output[] = "<div id='ewww-general-settings'>\n";
			$output[] = "<table class='form-table'>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_debug'>" . __('Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_debug' name='ewww_image_optimizer_debug' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_debug') == TRUE ? "checked='true'" : "" ) . " /> " . __('Use this to provide information for support purposes, or if you feel comfortable digging around in the code to fix a problem you are experiencing.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpegtran_copy'>" . __('Remove metadata', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><input type='checkbox' id='ewww_image_optimizer_jpegtran_copy' name='ewww_image_optimizer_jpegtran_copy' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpegtran_copy') == TRUE ? "checked='true'" : "" ) . " /> " . __('This will remove ALL metadata: EXIF and comments.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpg_lossy'>" . __('Lossy JPG optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_jpg_lossy' name='ewww_image_optimizer_jpg_lossy' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_lossy') == TRUE ? "checked='true'" : "" ) . " /> <b>" . __('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . __('While most users will not notice a difference in image quality, lossy means there IS a loss in image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "\n" .
				"<p class='description'>" . __('Requires an EWWW Image Optimizer Cloud Subscription.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . __('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_png_lossy'>" . __('Lossy PNG optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_png_lossy' name='ewww_image_optimizer_png_lossy' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy') == TRUE ? "checked='true'" : "" ) . " /> <b>" . __('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . __('While most users will not notice a difference in image quality, lossy means there IS a loss in image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "\n" .
				"<p class='description'>" . __('Uses pngquant locally. Use EWWW I.O. Cloud for even better lossy compression.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . __('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_delay'>" . __('Bulk Delay', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_delay' name='ewww_image_optimizer_delay' size='5' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_delay') . "'> " . __('Choose how long to pause between images (in seconds, 0 = disabled)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
	if (class_exists('Cloudinary') && Cloudinary::config_get("api_secret")) {
				$output[] = "<tr><th><label for='ewww_image_optimizer_enable_cloudinary'>" . __('Automatic Cloudinary upload', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_enable_cloudinary' name='ewww_image_optimizer_enable_cloudinary' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_enable_cloudinary') == TRUE ? "checked='true'" : "" ) . " /> " . __('When enabled, uploads to the Media Library will be transferred to Cloudinary after optimization. Cloudinary generates resizes, so only the full-size image is uploaded.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
	}
			$output[] = "</table>\n</div>\n";
			$output[] = "<div id='ewww-optimization-settings'>\n";
			$output[] = "<table class='form-table'>\n";
			if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
				$output[] = "<input id='ewww_image_optimizer_optipng_level' name='ewww_image_optimizer_optipng_level' type='hidden' value='2'>\n" .
					"<input id='ewww_image_optimizer_pngout_level' name='ewww_image_optimizer_pngout_level' type='hidden' value='2'>\n";
			} else {
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_optipng_level'>" . __('optipng optimization level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_optipng_level' name='ewww_image_optimizer_optipng_level'>\n" .
				"<option value='1'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 1 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . ': ' . sprintf(__('%d trial', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . "</option>\n" .
				"<option value='2'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 2 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 2) . ': ' . sprintf(__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 8) . "</option>\n" .
				"<option value='3'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 3 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 3) . ': ' . sprintf(__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 16) . "</option>\n" .
				"<option value='4'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 4 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 4) . ': ' . sprintf(__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 24) . "</option>\n" .
				"<option value='5'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 5 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 5) . ': ' . sprintf(__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 48) . "</option>\n" .
				"<option value='6'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 6 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 6) . ': ' . sprintf(__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 120) . "</option>\n" .
				"<option value='7'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 7 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 7) . ': ' . sprintf(__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 240) . "</option>\n" .
				"</select> (" . __('default', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "=2)</span>\n" .
				"<p class='description'>" . __('According to the author of optipng, 10 trials should satisfy most people, 30 trials should satisfy everyone.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_pngout_level'>" . __('pngout optimization level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_pngout_level' name='ewww_image_optimizer_pngout_level'>\n" .
				"<option value='0'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 0 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 0) . ': ' . __('Xtreme! (Slowest)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='1'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 1 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . ': ' . __('Intense (Slow)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='2'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 2 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 2) . ': ' . __('Longest Match (Fast)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='3'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 3 ? " selected='selected'" : "" ) . '>' . sprintf(__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 3) . ': ' . __('Huffman Only (Faster)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"</select> (" . __('default', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "=2)</span>\n" .
				"<p class='description'>" . sprintf(__('If you have CPU cycles to spare, go with level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 0) . "</p></td></tr>\n";
			}
				$output[] = "<tr><th><label for='ewww_image_optimizer_auto'>" . __('Scheduled optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_auto' name='ewww_image_optimizer_auto' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE ? "checked='true'" : "" ) . " /> " . __('This will enable scheduled optimization of unoptimized images for your theme, buddypress, and any additional folders you have configured below. Runs hourly: wp_cron only runs when your site is visited, so it may be even longer between optimizations.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_aux_paths'>" . __('Folders to optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td>" . sprintf(__('One path per line, must be within %s. Use full paths, not relative paths.', EWWW_IMAGE_OPTIMIZER_DOMAIN), ABSPATH) . "<br>\n";
				$output[] = "<textarea id='ewww_image_optimizer_aux_paths' name='ewww_image_optimizer_aux_paths' rows='3' cols='60'>" . ( ( $aux_paths = ewww_image_optimizer_get_option( 'ewww_image_optimizer_aux_paths' ) ) ? implode( "\n", $aux_paths ) : "" ) . "</textarea>\n";
				$output[] = "<p class='description'>" . __( 'Provide paths containing images to be optimized using "Scan and Optimize" on the Bulk Optimize page or by Scheduled Optimization.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br>\n";
				$output[] = "<b><a href='https://wordpress.org/support/plugin/ewww-image-optimizer'>" . __('Please submit a support request in the forums to have folders created by a particular plugin auto-included in the future.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></b></p></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_noauto'>" . __('Disable Automatic Optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_noauto' name='ewww_image_optimizer_noauto' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_noauto') == TRUE ? "checked='true'" : "" ) . " /> " . __('Images will not be optimized on upload. Images may be optimized with the Bulk Optimize tools or with Scheduled optimization.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_include_media_paths'>" . __('Include Media Library Folders', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_include_media_paths' name='ewww_image_optimizer_include_media_paths' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_include_media_paths') == TRUE ? "checked='true'" : "" ) . " /> " . __('If you have disabled automatic optimization, enable this if you want Scheduled Optimization to include the latest two folders from the Media Library.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_skip_size'>" . __('Skip Small Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_skip_size' name='ewww_image_optimizer_skip_size' size='8' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_size') . "'> " . __('Do not optimize images smaller than this (in bytes)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_skip_png_size'>" . __('Skip Large PNG Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_skip_png_size' name='ewww_image_optimizer_skip_png_size' size='8' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_png_size') . "'> " . __('Do not optimize PNG images larger than this (in bytes)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_lossy_skip_full'>" . __('Exclude full-size images from lossy optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_lossy_skip_full' name='ewww_image_optimizer_lossy_skip_full' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_metadata_skip_full'>" . __('Exclude full-size images from metadata removal', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_metadata_skip_full' name='ewww_image_optimizer_metadata_skip_full' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_metadata_skip_full') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_skip_bundle'>" . __('Use System Paths', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_skip_bundle' name='ewww_image_optimizer_skip_bundle' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_skip_bundle') == TRUE ? "checked='true'" : "" ) . " /> " . sprintf(__('If you have already installed the utilities in a system location, such as %s or %s, use this to force the plugin to use those versions and skip the auto-installers.', EWWW_IMAGE_OPTIMIZER_DOMAIN), '/usr/local/bin', '/usr/bin') . "</td></tr>\n";
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_jpegtran'>" . __('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " jpegtran</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_jpegtran' name='ewww_image_optimizer_disable_jpegtran' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_jpegtran') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_optipng'>" . __('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " optipng</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_optipng' name='ewww_image_optimizer_disable_optipng' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_pngout'>" . __('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " pngout</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_pngout' name='ewww_image_optimizer_disable_pngout' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') == TRUE  ? "checked='true'" : "" ) . " /></td><tr>\n";
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_gifsicle'>" . __('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " gifsicle</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_gifsicle' name='ewww_image_optimizer_disable_gifsicle' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_gifsicle') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
			$output[] = "</table>\n</div>\n";
			$output[] = "<div id='ewww-conversion-settings'>\n";
			$output[] = "<p>" . __('Conversion is only available for images in the Media Library (except WebP). By default, all images have a link available in the Media Library for one-time conversion. Turning on individual conversion operations below will enable conversion filters any time an image is uploaded or modified.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br />\n" .
				"<b>" . __('NOTE:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . __('The plugin will attempt to update image locations for any posts that contain the images. You may still need to manually update locations/urls for converted images.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "\n" .
			"</p>\n";
			$output[] = "<table class='form-table'>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_disable_convert_links'>" . __('Hide Conversion Links', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label</th><td><input type='checkbox' id='ewww_image_optimizer_disable_convert_links' name='ewww_image_optimizer_disable_convert_links' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_convert_links') == TRUE ? "checked='true'" : "" ) . " /> " . __('Site or Network admins can use this to prevent other users from using the conversion links in the Media Library which bypass the settings below.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_delete_originals'>" . __('Delete originals', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_delete_originals' name='ewww_image_optimizer_delete_originals' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_delete_originals') == TRUE ? "checked='true'" : "" ) . " /> " . __('This will remove the original image from the server after a successful conversion.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_webp'>" . __('JPG/PNG to WebP', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_webp' name='ewww_image_optimizer_webp' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp') == TRUE ? "checked='true'" : "" ) . " /> <b>" . __('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b> ' . __('JPG to WebP conversion is lossy, but quality loss is minimal. PNG to WebP conversion is lossless.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .  "</span>\n" .
				"<p class='description'>" . __('Originals are never deleted, and WebP images should only be served to supported browsers.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='#webp-rewrite'>" .  __('You can use the rewrite rules below to serve WebP images with Apache.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_webp_for_cdn'>" . __('Alternative WebP Rewriting', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_webp_for_cdn' name='ewww_image_optimizer_webp_for_cdn' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp_for_cdn') == TRUE ? "checked='true'" : "" ) . " /> " . __('Uses output buffering and libxml functionality from PHP. Use this if the Apache rewrite rules do not work, or if your images are served from a CDN.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .  "</span></td></tr>";
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpg_to_png'>" . sprintf(__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'JPG', 'PNG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_jpg_to_png' name='ewww_image_optimizer_jpg_to_png' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_to_png') == TRUE ? "checked='true'" : "" ) . " /> <b>" . __('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . __('Removes metadata and increases cpu usage dramatically.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'>" . __('PNG is generally much better than JPG for logos and other images with a limited range of colors. Checking this option will slow down JPG processing significantly, and you may want to enable it only temporarily.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_png_to_jpg'>" . sprintf(__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'PNG', 'JPG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_png_to_jpg' name='ewww_image_optimizer_png_to_jpg' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_to_jpg') == TRUE ? "checked='true'" : "" ) . " /> <b>" . __('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . __('This is not a lossless conversion.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'>" . __('JPG is generally much better than PNG for photographic use because it compresses the image and discards data. PNGs with transparency are not converted by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n" .
				"<span><label for='ewww_image_optimizer_jpg_background'> " . __('JPG background color:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> #<input type='text' id='ewww_image_optimizer_jpg_background' name='ewww_image_optimizer_jpg_background' size='6' value='" . ewww_image_optimizer_jpg_background() . "' /> <span style='padding-left: 12px; font-size: 12px; border: solid 1px #555555; background-color: #" . ewww_image_optimizer_jpg_background() . "'>&nbsp;</span> " . __('HEX format (#123def)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</span>\n" .
				"<p class='description'>" . __('Background color is used only if the PNG has transparency. Leave this value blank to skip PNGs with transparency.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n" .
				"<span><label for='ewww_image_optimizer_jpg_quality'>" . __('JPG quality level:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> <input type='text' id='ewww_image_optimizer_jpg_quality' name='ewww_image_optimizer_jpg_quality' class='small-text' value='" . ewww_image_optimizer_jpg_quality() . "' /> " . __('Valid values are 1-100.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'>" . __('If JPG quality is blank, the plugin will attempt to set the optimal quality level or default to 92. Remember, this is a lossy conversion, so you are losing pixels, and it is not recommended to actually set the level here unless you want noticable loss of image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_gif_to_png'>" . sprintf(__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'GIF', 'PNG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_gif_to_png' name='ewww_image_optimizer_gif_to_png' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') == TRUE ? "checked='true'" : "" ) . " /> " . __('No warnings here, just do it.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'> " . __('PNG is generally better than GIF, but animated images cannot be converted.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
			$output[] = "</table>\n</div>\n";
			$output[] = "<p class='submit'><input type='submit' class='button-primary' value='" . __('Save Changes', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "' /></p>\n";
		$output[] = "</form>\n";
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp_for_cdn' ) ) {
		$output[] = "<form id='ewww-webp-rewrite'>\n";
			$output[] = "<p>" . __('There are many ways to serve WebP images to visitors with supported browsers. You may choose any you wish, but it is recommended to serve them with an .htaccess file using mod_rewrite and mod_headers. The plugin can insert the rules for you if the file is writable, or you can edit .htaccess yourself.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n";
			if ( ! ewww_image_optimizer_webp_rewrite_verify() ) {
				$output[] = "<img id='webp-image' src='" . plugins_url('/test.png', __FILE__) . "' style='float: right; padding: 0 0 10px 10px;'>\n" .
				"<p id='ewww-webp-rewrite-status'><b>" . __('Rules verified successfully', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b></p>\n";
			} else {
			$output[] = "<pre id='webp-rewrite-rules' style='background: white; font-color: black; border: 1px solid black; clear: both; padding: 10px;'>\n" .
				"&lt;IfModule mod_rewrite.c&gt;\n" .
				"RewriteEngine On\n" .
				"RewriteCond %{HTTP_ACCEPT} image/webp\n" .
				"RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$\n" .
				"RewriteCond %{REQUEST_FILENAME}\.webp -f\n" .
				"RewriteRule (.+)\.(jpe?g|png)$ %{REQUEST_FILENAME}.webp [T=image/webp,E=accept:1]\n" .
				"&lt;/IfModule&gt;\n" .
				"&lt;IfModule mod_headers.c&gt;\n" .
				"Header append Vary Accept env=REDIRECT_accept\n" .
				"&lt;/IfModule&gt;\n" .
				"AddType image/webp .webp</pre>\n" .
				"<img id='webp-image' src='" . plugins_url('/test.png', __FILE__) . "' style='float: right; padding-left: 10px;'>\n" .
				"<p id='ewww-webp-rewrite-status'>" . __('The image to the right will display a WebP image with WEBP in white text, if your site is serving WebP images and your browser supports WebP.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n" .
				"<button type='submit' class='button-secondary action'>" . __('Insert Rewrite Rules', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</button>\n";

			}
		$output[] = "</form>\n";
		}
		$output[] = "</div><!-- end container left -->\n";
		$output[] = "<div id='ewww-container-right' style='border: 1px solid #e5e5e5; float: right; margin-left: -215px; padding: 0em 1.5em 1em; background-color: #fff; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04); display: inline-block; width: 174px;'>\n" .
			"<h3>Support EWWW I.O.</h3>\n" .
			"<p>Would you like to help support development of this plugin?<br />\n" .
			"<p>Contribute directly by <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=MKMQKCBFFG3WW'>donating with Paypal</a>.</p>\n" .
			"<b>OR</b><br />\n" .
			"Use any of these referral links to show your appreciation:</p>\n" .
			"<p><b>Web Hosting:</b><br>\n" .
				"<a href='https://partners.a2hosting.com/solutions.php?id=5959&url=638'>A2 Hosting</a> with automatic EWWW IO setup<br>\n" .
				"<a href='http://www.dreamhost.com/r.cgi?132143'>Dreamhost</a><br>\n" .
				"<a href='http://www.bluehost.com/track/nosilver4u'>Bluehost</a><!-- <br>\n" .
				"<a href='http://www.liquidweb.com/?RID=nosilver4u'>liquidweb</a><br>\n" .
				"<a href='http://www.stormondemand.com/?RID=nosilver4u'>Storm on Demand</a>-->\n" .
			"</p>\n" .
			"<p><b>VPS:</b><br>\n" .
				"<a href='http://www.bluehost.com/track/nosilver4u?page=/vps'>Bluehost</a><br>\n" .
				"<a href='https://www.digitalocean.com/?refcode=89ef0197ec7e'>DigitalOcean</a><br>\n" .
				"<a href='https://clientarea.ramnode.com/aff.php?aff=1469'>RamNode</a><br>\n" .
				"<a href='http://www.vultr.com/?ref=6814210'>VULTR</a>\n" .
			"</p>\n" .
			"<p><b>CDN Network:</b><br>Add MaxCDN to increase website speeds dramatically! <a target='_blank' href='http://tracking.maxcdn.com/c/91625/36539/378'>Sign Up Now and Save 25%</a> (100% Money Back Guarantee for 30 days). Integrate it within Wordpress using the W3 Total Cache plugin.</p>\n" .
		"</div>\n" .
	"</div>\n";
	$ewww_debug .= '--' . ini_get('max_execution_time') . '--<br>';
	echo apply_filters( 'ewww_image_optimizer_settings', $output );
	ewwwio_memory( __FUNCTION__ );
}

function ewww_image_optimizer_filter_settings_page($input) {
	$output = '';
	foreach ($input as $line) {
		if ( EWWW_IMAGE_OPTIMIZER_CLOUD && preg_match( "/class='nocloud'/", $line ) ) {
			continue;
		} else {
			$output .= $line;
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return $output;
}

function ewwwio_memory_output() {
	if ( WP_DEBUG ) {
		global $ewww_memory;
		$timestamp = date('y-m-d h:i:s.u') . "  ";
		if (!file_exists(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log'))
			touch(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log');
		file_put_contents(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log', $timestamp . $ewww_memory, FILE_APPEND);
	}
}
?>
