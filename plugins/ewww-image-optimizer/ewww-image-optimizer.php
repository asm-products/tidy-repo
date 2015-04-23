<?php
/**
 * Integrate image optimizers into WordPress.
 * @version 2.3.2
 * @package EWWW_Image_Optimizer
 */
/*
Plugin Name: EWWW Image Optimizer
Plugin URI: http://wordpress.org/extend/plugins/ewww-image-optimizer/
Description: Reduce file sizes for images within WordPress including NextGEN Gallery and GRAND FlAGallery. Uses jpegtran, optipng/pngout, and gifsicle.
Author: Shane Bishop
Text Domain: ewww-image-optimizer
Version: 2.3.2
Author URI: https://ewww.io/
License: GPLv3
*/

// TODO: make mention of wp-plugins/ewwwio repo on github

// Constants
define('EWWW_IMAGE_OPTIMIZER_DOMAIN', 'ewww-image-optimizer');
// this is the full path of the plugin file itself
define('EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE', __FILE__);
if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' ) {
	// this is the path of the plugin file relative to the plugins\ folder
	define('EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL', 'ewww-image-optimizer\ewww-image-optimizer.php');
	// the folder where we install optimization tools
	define('EWWW_IMAGE_OPTIMIZER_TOOL_PATH', WP_CONTENT_DIR . '\ewww\\');
} else {
	// this is the path of the plugin file relative to the plugins/ folder
	define('EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL', 'ewww-image-optimizer/ewww-image-optimizer.php');
	// the folder where we install optimization tools
	define('EWWW_IMAGE_OPTIMIZER_TOOL_PATH', WP_CONTENT_DIR . '/ewww/');
}
// this is the full system path to the plugin folder
define('EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'common.php');

// Hooks
add_action('admin_action_ewww_image_optimizer_install_pngout', 'ewww_image_optimizer_install_pngout');

// check to see if the cloud constant is defined (which would mean we've already run init) and then set it properly if not
function ewww_image_optimizer_cloud_init() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_cloud_init()</b><br>";
	if (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD') && ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg') && ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif')) {
		define('EWWW_IMAGE_OPTIMIZER_CLOUD', TRUE);
	} elseif (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD')) {
		define('EWWW_IMAGE_OPTIMIZER_CLOUD', FALSE);
	}
	ewwwio_memory( __FUNCTION__ );
}

function ewww_image_optimizer_exec_init() {
	global $ewww_debug;
	global $ewww_admin;
	$ewww_debug .= "<b>ewww_image_optimizer_exec_init()</b><br>";
	if ( $ewww_admin ) {
		$ewww_debug .= 'we are in the admin, feel free to shout<br>';
	} else {
		$ewww_debug .= 'no admin, be quiet<br>';
	}
	if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL)) {
		// set the binary-specific network settings if they have been POSTed
		if (isset($_POST['ewww_image_optimizer_delay'])) {
			if (empty($_POST['ewww_image_optimizer_skip_check'])) $_POST['ewww_image_optimizer_skip_check'] = '';
			update_site_option('ewww_image_optimizer_skip_check', $_POST['ewww_image_optimizer_skip_check']);
			if (empty($_POST['ewww_image_optimizer_skip_bundle'])) $_POST['ewww_image_optimizer_skip_bundle'] = '';
			update_site_option('ewww_image_optimizer_skip_bundle', $_POST['ewww_image_optimizer_skip_bundle']);
			if ( ! empty( $_POST['ewww_image_optimizer_optipng_level'] ) ) {
				update_site_option('ewww_image_optimizer_optipng_level', $_POST['ewww_image_optimizer_optipng_level']);
				update_site_option('ewww_image_optimizer_pngout_level', $_POST['ewww_image_optimizer_pngout_level']);
			}
			if (empty($_POST['ewww_image_optimizer_disable_jpegtran'])) $_POST['ewww_image_optimizer_disable_jpegtran'] = '';
			update_site_option('ewww_image_optimizer_disable_jpegtran', $_POST['ewww_image_optimizer_disable_jpegtran']);
			if (empty($_POST['ewww_image_optimizer_disable_optipng'])) $_POST['ewww_image_optimizer_disable_optipng'] = '';
			update_site_option('ewww_image_optimizer_disable_optipng', $_POST['ewww_image_optimizer_disable_optipng']);
			if (empty($_POST['ewww_image_optimizer_disable_gifsicle'])) $_POST['ewww_image_optimizer_disable_gifsicle'] = '';
			update_site_option('ewww_image_optimizer_disable_gifsicle', $_POST['ewww_image_optimizer_disable_gifsicle']);
			if (empty($_POST['ewww_image_optimizer_disable_pngout'])) $_POST['ewww_image_optimizer_disable_pngout'] = '';
			update_site_option('ewww_image_optimizer_disable_pngout', $_POST['ewww_image_optimizer_disable_pngout']);
		}
	}
	// register all the binary-specific EWWW IO settings
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_check');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_bundle');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_optipng_level');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_pngout_level');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_jpegtran');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_optipng');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_gifsicle');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_pngout');
	// If cloud is fully enabled, we're going to skip all the checks related to the bundled tools
	if(EWWW_IMAGE_OPTIMIZER_CLOUD) {
		$ewww_debug .= 'cloud options enabled, shutting off binaries<br>';
		ewww_image_optimizer_disable_tools();
	// Check if this is an unsupported OS (not Linux or Mac OSX or FreeBSD or Windows or SunOS)
	} elseif('Linux' != PHP_OS && 'Darwin' != PHP_OS && 'FreeBSD' != PHP_OS && 'WINNT' != PHP_OS && 'SunOS' != PHP_OS) {
		// call the function to display a notice
		add_action('network_admin_notices', 'ewww_image_optimizer_notice_os');
		add_action('admin_notices', 'ewww_image_optimizer_notice_os');
		// turn off all the tools
		$ewww_debug .= 'unsupported OS, disabling tools: ' . PHP_OS . '<br>';
		ewww_image_optimizer_disable_tools();
	} else {
		add_action( 'load-upload.php', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-media-new.php', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-media_page_ewww-image-optimizer-bulk', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-settings_page_ewww-image-optimizer/ewww-image-optimizer', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-plugins.php', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-ims_gallery_page_ewww-ims-optimize', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-media_page_ewww-image-optimizer-unoptimized', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-flagallery_page_flag-manage-gallery', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-gallery_page_nggallery-manage-gallery', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-galleries_page_nggallery-manage-gallery', 'ewww_image_optimizer_tool_init' );
//		add_action( 'load-', 'ewww_image_optimizer_tool_init' );
	} 
	ewwwio_memory( __FUNCTION__ );
}

// check for binary installation and availability
function ewww_image_optimizer_tool_init( $hook = false, $admin = true ) {
	global $ewww_debug;
//	global $ewww_admin;
	$ewww_debug .= "<b>ewww_image_optimizer_tool_init()</b><br>";
	if ( $admin ) {
		$ewww_debug .= 'we are in the admin, feel free to shout<br>';
	} else {
		$ewww_debug .= 'no admin, be quiet<br>';
	}
	// make sure the bundled tools are installed
	if( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_bundle' ) ) {
		ewww_image_optimizer_install_tools ();
	}
	if ( $admin ) {
		//then we run the function to check for optimization utilities
		add_action( 'network_admin_notices', 'ewww_image_optimizer_notice_utils' );
		add_action( 'admin_notices', 'ewww_image_optimizer_notice_utils' );
	} else {
		if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
			$ewww_debug .= 'cloud options enabled, shutting off binaries<br>';
			ewww_image_optimizer_disable_tools();
		}
		ewww_image_optimizer_notice_utils();
	}
}

// set some default option values
function ewww_image_optimizer_set_defaults() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_set_defaults()</b><br>";
	// set a few defaults
	add_site_option('ewww_image_optimizer_disable_pngout', TRUE);
	add_site_option('ewww_image_optimizer_optipng_level', 2);
	add_site_option('ewww_image_optimizer_pngout_level', 2);
}

// tells the user they are on an unsupported operating system
function ewww_image_optimizer_notice_os() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_notice_os()</b><br>";
	echo "<div id='ewww-image-optimizer-warning-os' class='error'><p><strong>" . __('EWWW Image Optimizer is supported on Linux, FreeBSD, Mac OSX, and Windows', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</strong> " . sprintf(__('Unfortunately, the EWWW Image Optimizer plugin does not work with %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), htmlentities(PHP_OS)) . ".</p></div>";
}   

// generates the source and destination paths for the executables that we bundle with the plugin based on the operating system
function ewww_image_optimizer_install_paths () {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_install_paths()</b><br>";
	if (PHP_OS == 'WINNT') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle.exe';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng.exe';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran.exe';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant.exe';
		$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp.exe';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle.exe';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng.exe';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran.exe';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant.exe';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp.exe';
	}
	if (PHP_OS == 'Darwin') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle-mac';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng-mac';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-mac';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant-mac';
		$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-mac8';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'SunOS') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle-sol';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng-sol';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-sol';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant-sol';
		$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-sol';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'FreeBSD') {
		$arch_type = php_uname('m');
		$ewww_debug .= "CPU architecture: $arch_type<br>";
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle-fbsd';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng-fbsd';
		if ($arch_type == 'amd64') {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-fbsd64';
		} else {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-fbsd';
		}
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant-fbsd';
		if ($arch_type == 'amd64') {
			$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-fbsd64';
		} else {
			$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-fbsd';
		}
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'Linux') {
		$arch_type = php_uname('m');
		$ewww_debug .= "CPU architecture: $arch_type<br>";
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle-linux';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng-linux';
		if ($arch_type == 'x86_64') {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-linux64';
			$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-linux864';
		} else {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-linux';
			$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-linux8';
		}
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant-linux';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	$ewww_debug .= "generated paths:<br>$jpegtran_src<br>$optipng_src<br>$gifsicle_src<br>$pngquant_src<br>$webp_src<br>$jpegtran_dst<br>$optipng_dst<br>$gifsicle_dst<br>$pngquant_dst<br>$webp_dst<br>";
	ewwwio_memory( __FUNCTION__ );
	return array($jpegtran_src, $optipng_src, $gifsicle_src, $pngquant_src, $webp_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst, $pngquant_dst, $webp_dst);
}

// installs the executables that are bundled with the plugin
function ewww_image_optimizer_install_tools () {
	global $ewww_debug;
	global $ewww_admin;
	$ewww_debug .= "<b>ewww_image_optimizer_install_tools()</b><br>";
	if ( $ewww_admin ) {
		$ewww_debug .= 'we are in the admin, feel free to shout<br>';
	} else {
		$ewww_debug .= 'no admin, be quiet<br>';
	}
	$ewww_debug .= "Checking/Installing tools in " . EWWW_IMAGE_OPTIMIZER_TOOL_PATH . "<br>";
	$toolfail = false;
	if (!is_dir(EWWW_IMAGE_OPTIMIZER_TOOL_PATH)) {
		$ewww_debug .= "Folder doesn't exist, creating...<br>";
		if ( ! mkdir( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) {
			if ( $ewww_admin ) {
				echo "<div id='ewww-image-optimizer-warning-tool-install' class='error'><p><strong>" . __('EWWW Image Optimizer could not create the tool folder', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ": " . htmlentities(EWWW_IMAGE_OPTIMIZER_TOOL_PATH) . ".</strong> " . __('Please adjust permissions or create the folder', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</p></div>";
			}
			$ewww_debug .= "Couldn't create folder<br>";
			return;
		}
	} else {
		if ( ! is_writable(EWWW_IMAGE_OPTIMIZER_TOOL_PATH)) {
			$ewww_debug .= 'wp-content/ewww is not writable, not installing anything<br>';
			return;
		}
		$ewww_perms = substr(sprintf('%o', fileperms(EWWW_IMAGE_OPTIMIZER_TOOL_PATH)), -4);
		$ewww_debug .= "wp-content/ewww permissions: $ewww_perms <br>";
	}
	list ($jpegtran_src, $optipng_src, $gifsicle_src, $pngquant_src, $webp_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst, $pngquant_dst, $webp_dst) = ewww_image_optimizer_install_paths();
	if ( ! file_exists( $jpegtran_dst ) || filesize( $jpegtran_dst ) != filesize( $jpegtran_src ) ) {
		$ewww_debug .= 'jpegtran not found or different size, installing<br>';
		//$ewww_debug .= "jpegtran found, different size, attempting to replace<br>";
		if (!copy($jpegtran_src, $jpegtran_dst)) {
			$toolfail = true;
			$ewww_debug .= "Couldn't copy jpegtran<br>";
		}
	}
	// install 32-bit jpegtran at jpegtran-alt for some weird 64-bit hosts
	$arch_type = php_uname('m');
	if (PHP_OS == 'Linux' && $arch_type == 'x86_64') {
		$ewww_debug .= "64-bit linux detected while installing tools<br>";
		$jpegtran32_src = substr($jpegtran_src, 0, -2);
		$jpegtran32_dst = $jpegtran_dst . '-alt';
		if (!file_exists($jpegtran32_dst) || (ewww_image_optimizer_md5check($jpegtran32_dst) && filesize($jpegtran32_dst) != filesize($jpegtran32_src))) {
			$ewww_debug .= "copying $jpegtran32_src to $jpegtran32_dst<br>";
			if (!copy($jpegtran32_src, $jpegtran32_dst)) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				$ewww_debug .= "Couldn't copy 32-bit jpegtran to jpegtran-alt<br>";
			}
			$jpegtran32_perms = substr(sprintf('%o', fileperms($jpegtran32_dst)), -4);
			$ewww_debug .= "jpegtran-alt (32-bit) permissions: $jpegtran32_perms<br>";
			if ($jpegtran32_perms != '0755') {
				if (!chmod($jpegtran32_dst, 0755)) {
					$ewww_debug .= "couldn't set jpegtran-alt permissions<br>";
				}
			}
		}
	}
	if (!file_exists($gifsicle_dst) || filesize($gifsicle_dst) != filesize($gifsicle_src)) {
		$ewww_debug .= "gifsicle not found or different size, installing<br>";
		//$ewww_debug .= "gifsicle found, different size, attempting to replace<br>";
		if (!copy($gifsicle_src, $gifsicle_dst)) {
			$toolfail = true;
			$ewww_debug .= "Couldn't copy gifsicle<br>";
		}
	}
	if (!file_exists($optipng_dst) || filesize($optipng_dst) != filesize($optipng_src)) {
		$ewww_debug .= "optipng not found or different size, installing<br>";
		if (!copy($optipng_src, $optipng_dst)) {
			$toolfail = true;
			$ewww_debug .= "Couldn't copy optipng<br>";
		}
	}
	if (!file_exists($pngquant_dst) || filesize($pngquant_dst) != filesize($pngquant_src)) {
		$ewww_debug .= "pngquant not found or different size, installing<br>";
		if (!copy($pngquant_src, $pngquant_dst)) {
			$toolfail = true;
			$ewww_debug .= "Couldn't copy pngquant<br>";
		}
	}
	if (!file_exists($webp_dst) || filesize($webp_dst) != filesize($webp_src)) {
		$ewww_debug .= "webp not found or different size, installing<br>";
		if (!copy($webp_src, $webp_dst)) {
			$toolfail = true;
			$ewww_debug .= "Couldn't copy webp<br>";
		}
	}
	// install special version of cwebp for Mac OSX 10.7 systems
	if (PHP_OS == 'Darwin') {
		$webp7_dst = $webp_dst . '-alt';
		$webp7_src = str_replace('mac8', 'mac7', $webp_src);
		if (!file_exists($webp7_dst) || (ewww_image_optimizer_md5check($webp7_dst) && filesize($webp7_dst) != filesize($webp7_src))) {
			$ewww_debug .= "copying $webp7_src to $webp7_dst<br>";
			if (!copy($webp7_src, $webp7_dst)) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				$ewww_debug .= "Couldn't copy OSX 10.7 cwebp to cwebp-alt<br>";
			}
			$webp7_perms = substr(sprintf('%o', fileperms($webp7_dst)), -4);
			$ewww_debug .= "cwebp7-alt (OSX 10.7) permissions: $webp7_perms<br>";
			if ($webp7_perms != '0755') {
				if (!chmod($webp7_dst, 0755)) {
					$ewww_debug .= "couldn't set cwebp7-alt permissions<br>";
				}
			}
		}
	}

	// install libjpeg6 version of cwebp for older systems
	if (PHP_OS == 'Linux') {
		$webp6_dst = $webp_dst . '-alt';
		$webp6_src = str_replace('linux8', 'linux6', $webp_src);
		if (!file_exists($webp6_dst) || (ewww_image_optimizer_md5check($webp6_dst) && filesize($webp6_dst) != filesize($webp6_src))) {
			$ewww_debug .= "copying $webp6_src to $webp6_dst<br>";
			if (!copy($webp6_src, $webp6_dst)) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				$ewww_debug .= "Couldn't copy libjpeg6 cwebp to cwebp-alt<br>";
			}
			$webp6_perms = substr(sprintf('%o', fileperms($webp6_dst)), -4);
			$ewww_debug .= "cwebp6-alt (libjpeg6) permissions: $webp6_perms<br>";
			if ($webp6_perms != '0755') {
				if (!chmod($webp6_dst, 0755)) {
					$ewww_debug .= "couldn't set cwebp6-alt permissions<br>";
				}
			}
		}
	}

	if ( PHP_OS != 'WINNT' && ! $toolfail ) {
		$ewww_debug .= "Linux/UNIX style OS, checking permissions<br>";
		$jpegtran_perms = substr(sprintf('%o', fileperms($jpegtran_dst)), -4);
		$ewww_debug .= "jpegtran permissions: $jpegtran_perms<br>";
		if ($jpegtran_perms != '0755') {
			if (!chmod($jpegtran_dst, 0755)) {
				$toolfail = true;
				$ewww_debug .= "couldn't set jpegtran permissions<br>";
			}
		}
		$gifsicle_perms = substr(sprintf('%o', fileperms($gifsicle_dst)), -4);
		$ewww_debug .= "gifsicle permissions: $gifsicle_perms<br>";
		if ($gifsicle_perms != '0755') {
			if (!chmod($gifsicle_dst, 0755)) {
				$toolfail = true;
				$ewww_debug .= "couldn't set gifsicle permissions<br>";
			}
		}
		$optipng_perms = substr(sprintf('%o', fileperms($optipng_dst)), -4);
		$ewww_debug .= "optipng permissions: $optipng_perms<br>";
		if ($optipng_perms != '0755') {
			if (!chmod($optipng_dst, 0755)) {
				$toolfail = true;
				$ewww_debug .= "couldn't set optipng permissions<br>";
			}
		}
		$pngquant_perms = substr(sprintf('%o', fileperms($pngquant_dst)), -4);
		$ewww_debug .= "pngquant permissions: $pngquant_perms<br>";
		if ($pngquant_perms != '0755') {
			if (!chmod($pngquant_dst, 0755)) {
				$toolfail = true;
				$ewww_debug .= "couldn't set pngquant permissions<br>";
			}
		}
		$webp_perms = substr(sprintf('%o', fileperms($webp_dst)), -4);
		$ewww_debug .= "webp permissions: $webp_perms<br>";
		if ($webp_perms != '0755') {
			if (!chmod($webp_dst, 0755)) {
				$toolfail = true;
				$ewww_debug .= "couldn't set webp permissions<br>";
			}
		}
	}
	if ( $toolfail && $ewww_admin ) {
		echo "<div id='ewww-image-optimizer-warning-tool-install' class='error'><p><strong>" . sprintf(__('EWWW Image Optimizer could not install tools in %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), htmlentities(EWWW_IMAGE_OPTIMIZER_TOOL_PATH)) . ".</strong> " . sprintf(__('Please adjust permissions or create the folder. If you have installed the tools elsewhere on your system, check the option to %s.', EWWW_IMAGE_OPTIMIZER_DOMAIN), __('Use System Paths', EWWW_IMAGE_OPTIMIZER_DOMAIN)) . " " . sprintf(__('For more details, visit the %1$s or the %2$s.', EWWW_IMAGE_OPTIMIZER_DOMAIN), "<a href='options-general.php?page=" . EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL . "'>" . __('Settings Page', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>", "<a href='http://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" . __('Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>.</p></div>");
	}
	ewwwio_memory( __FUNCTION__ );
}

// we check for safe mode and exec, then also direct the user where to go if they don't have the tools installed
// this is another function called by hook usually
function ewww_image_optimizer_notice_utils() {
	global $ewww_debug;
	global $ewww_admin;
	$ewww_debug .= "<b>ewww_image_optimizer_notice_utils()</b><br>";
	if ( $ewww_admin ) {
		$ewww_debug .= 'we are in the admin, feel free to shout<br>';
	} else {
		$ewww_debug .= 'no admin, be quiet<br>';
	}
	// Check if exec is disabled
	if(ewww_image_optimizer_exec_check()) {
		//display a warning if exec() is disabled, can't run much of anything without it
		if ( $ewww_admin ) {
			echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . __('EWWW Image Optimizer requires exec(). Your system administrator has disabled this function.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></div>";
		}
		define('EWWW_IMAGE_OPTIMIZER_NOEXEC', true);
		$ewww_debug .= 'exec seems to be disabled<br>';
		ewww_image_optimizer_disable_tools();
		return;
		// otherwise, query the php settings for safe mode
	} elseif (ewww_image_optimizer_safemode_check()) {
		// display a warning to the user
		if ( $ewww_admin ) {
			echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . __('Safe Mode is turned on for PHP. This plugin cannot operate in Safe Mode.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></div>";
		}
		define('EWWW_IMAGE_OPTIMIZER_NOEXEC', true);
		$ewww_debug .= 'safe mode appears to be enabled<br>';
		ewww_image_optimizer_disable_tools();
		return;
	} else {
		define('EWWW_IMAGE_OPTIMIZER_NOEXEC', false);
	}

	// attempt to retrieve values for utility paths, and store them in the appropriate variables
	$required = ewww_image_optimizer_path_check();
	// set the variables false otherwise
	$skip_jpegtran_check = false;
	$skip_optipng_check = false;
	$skip_gifsicle_check = false;
	$skip_pngout_check = false;
	$skip_pngquant_check = true;
	$skip_webp_check = true;
	// if the user has disabled a variable, we aren't going to bother checking to see if it is there
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_jpegtran')) {
		$skip_jpegtran_check = true;
	}
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng')) {
		$skip_optipng_check = true;
	}
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_gifsicle')) {
		$skip_gifsicle_check = true;
	}
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout')) {
		$skip_pngout_check = true;
	}
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy')) {
		$skip_pngquant_check = false;
	}
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_webp')) {
		$skip_webp_check = false;
	}
	// we are going to store our validation results in $missing
	$missing = array();
	// go through each of the required tools
	foreach($required as $key => $req){
		// if the tool wasn't found, add it to the $missing array if we are supposed to check the tool in question
		switch($key) {
			case 'JPEGTRAN':
				if (!$skip_jpegtran_check && empty($req)) {
					$missing[] = 'jpegtran';
					$req = false;
				}
				define('EWWW_IMAGE_OPTIMIZER_' . $key, $req);
				break; 
			case 'OPTIPNG':
				if (!$skip_optipng_check && empty($req)) {
					$missing[] = 'optipng';
					$req = false;
				}
				define('EWWW_IMAGE_OPTIMIZER_' . $key, $req);
				break;
			case 'GIFSICLE':
				if (!$skip_gifsicle_check && empty($req)) {
					$missing[] = 'gifsicle';
					$req = false;
				}
				define('EWWW_IMAGE_OPTIMIZER_' . $key, $req);
				break;
			case 'PNGOUT':
				if (!$skip_pngout_check && empty($req)) {
					$missing[] = 'pngout';
					$req = false;
				}
				define('EWWW_IMAGE_OPTIMIZER_' . $key, $req);
				break;
			case 'PNGQUANT':
				if (!$skip_pngquant_check && empty($req)) {
					$missing[] = 'pngquant';
					$req = false;
				}
				define('EWWW_IMAGE_OPTIMIZER_' . $key, $req);
				break;
			case 'WEBP':
				if (!$skip_webp_check && empty($req)) {
					$missing[] = 'webp';
					$req = false;
				}
				define('EWWW_IMAGE_OPTIMIZER_' . $key, $req);
				break;
		}
	}
	// expand the missing utilities list for use in the error message
	$msg = implode( ', ', $missing );
	// if there is a message, display the warning
	if( ! empty( $msg ) && $ewww_admin ){
		echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . sprintf(__('EWWW Image Optimizer uses %1$s, %2$s, %3$s, %4$s, %5$s, and %6$s. You are missing: %7$s. Please install via the %8$s or the %9$s.', EWWW_IMAGE_OPTIMIZER_DOMAIN), "<a href='http://jpegclub.org/jpegtran/'>jpegtran</a>", "<a href='http://optipng.sourceforge.net/'>optipng</a>", "<a href='http://advsys.net/ken/utils.htm'>pngout</a>", "<a href='http://pngquant.org/'>pngquant</a>", "<a href='http://www.lcdf.org/gifsicle/'>gifsicle</a>", "<a href='https://developers.google.com/speed/webp/'>cwebp</a>", $msg, "<a href='options-general.php?page=" . EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL . "'>" . __('Settings Page', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>", "<a href='http://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" . __('Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>") . "</p></div>";
	ewwwio_memory( __FUNCTION__ );
	}
}

// function to check if exec() is disabled
function ewww_image_optimizer_exec_check() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_exec_check()</b><br>";
	$disabled = ini_get('disable_functions');
	$ewww_debug .= "disable_functions = $disabled <br>";
	$suhosin_disabled = ini_get('suhosin.executor.func.blacklist');
	$ewww_debug .= "suhosin_blacklist = $suhosin_disabled <br>";
	if(preg_match('/([\s,]+exec|^exec)/', $disabled) || preg_match('/([\s,]+exec|^exec)/', $suhosin_disabled)) {
	ewwwio_memory( __FUNCTION__ );
		return true;
	} else {
	ewwwio_memory( __FUNCTION__ );
		return false;
	}
}

// function to check if safe mode is on
function ewww_image_optimizer_safemode_check() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_safemode_check()</b><br>";
	$safe_mode = ini_get('safe_mode');
	$ewww_debug .= "safe_mode = $safe_mode<br>";
	switch (strtolower($safe_mode)) {
		case 'off':
	ewwwio_memory( __FUNCTION__ );
			return false;
		case 'on':
		case true:
	ewwwio_memory( __FUNCTION__ );
			return true;
		default:
	ewwwio_memory( __FUNCTION__ );
			return false;
	}
}

// If the utitilites are in the content folder, we use that. Otherwise, we check system paths. We also do a basic check to make sure we weren't given a malicious path.
function ewww_image_optimizer_path_check ( $j = true, $o = true, $g = true, $p = true, $q = true, $w = true) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_path_check()</b><br>";
	$jpegtran = false;
	$optipng = false;
	$gifsicle = false;
	$pngout = false;
	$pngquant = false;
	$webp = false;
	// for Windows, everything must be in the wp-content/ewww folder, so that is all we check
	if ('WINNT' == PHP_OS) {
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran.exe') && $j) {
			$jpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran.exe';
			$ewww_debug .= "found $jpt, testing...<br>";
			if (ewww_image_optimizer_tool_found('"' . $jpt . '"', 'j') && ewww_image_optimizer_md5check($jpt)) {
				$jpegtran = '"' . $jpt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng.exe') && $o) {
			$opt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng.exe';
			$ewww_debug .= "found $opt, testing...<br>";
			if (ewww_image_optimizer_tool_found('"' . $opt . '"', 'o') && ewww_image_optimizer_md5check($opt)) {
				$optipng = '"' . $opt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle.exe') && $g) {
			$gpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle.exe';
			$ewww_debug .= "found $gpt, testing...<br>";
			if (ewww_image_optimizer_tool_found('"' . $gpt . '"', 'g') && ewww_image_optimizer_md5check($gpt)) {
				$gifsicle = '"' . $gpt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout.exe') && $p) {
			$ppt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout.exe';
			$ewww_debug .= "found $ppt, testing...<br>";
			if (ewww_image_optimizer_tool_found('"' . $ppt . '"', 'p') && ewww_image_optimizer_md5check($ppt)) {
				$pngout = '"' . $ppt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant.exe') && $q) {
			$qpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant.exe';
			$ewww_debug .= "found $qpt, testing...<br>";
			if (ewww_image_optimizer_tool_found('"' . $qpt . '"', 'q') && ewww_image_optimizer_md5check($qpt)) {
				$pngquant = '"' . $qpt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp.exe') && $w) {
			$wpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp.exe';
			$ewww_debug .= "found $wpt, testing...<br>";
			if (ewww_image_optimizer_tool_found('"' . $wpt . '"', 'w') && ewww_image_optimizer_md5check($wpt)) {
				$webp = '"' . $wpt . '"';
			}
		}
	} else {
		// check to see if the user has disabled using bundled binaries
		$use_system = ewww_image_optimizer_get_option('ewww_image_optimizer_skip_bundle');
		if ($j) {
			// first check for the jpegtran binary in the ewww tool folder
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran') && !$use_system) {
				$jpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
				$ewww_debug .= "found $jpt, testing...<br>";
				if (ewww_image_optimizer_md5check($jpt) && ewww_image_optimizer_mimetype($jpt, 'b')) {
					$jpt = ewww_image_optimizer_escapeshellcmd ( $jpt );
					if (ewww_image_optimizer_tool_found($jpt, 'j')) {
						$jpegtran = $jpt;
					}
				}
			}
			// if the standard jpegtran binary didn't work, see if the user custom compiled one and check that
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran-custom') && !$jpegtran && !$use_system) {
				$jpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran-custom';
				$ewww_debug .= "found $jpt, testing...<br>";
				if (filesize($jpt) > 15000 && ewww_image_optimizer_mimetype($jpt, 'b')) {
					$jpt = ewww_image_optimizer_escapeshellcmd ( $jpt );
					if (ewww_image_optimizer_tool_found($jpt, 'j')) {
						$jpegtran = $jpt;
					}
				}
			}
			// see if the alternative binary works
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran-alt') && !$jpegtran && !$use_system) {
				$jpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran-alt';
				$ewww_debug .= "found $jpt, testing...<br>";
				if (filesize($jpt) > 15000 && ewww_image_optimizer_mimetype($jpt, 'b')) {
					$jpt = ewww_image_optimizer_escapeshellcmd ( $jpt );
					if (ewww_image_optimizer_tool_found($jpt, 'j')) {
						$jpegtran = $jpt;
					}
				}
			}
			// if we still haven't found a usable binary, try a system-installed version
			if (!$jpegtran) {
				$jpegtran = ewww_image_optimizer_find_binary('jpegtran', 'j');
			}
		}
		if ($o) {
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng') && !$use_system) {
				$opt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
				$ewww_debug .= "found $opt, testing...<br>";
				if (ewww_image_optimizer_md5check($opt) && ewww_image_optimizer_mimetype($opt, 'b')) {
					$opt = ewww_image_optimizer_escapeshellcmd ( $opt );
					if (ewww_image_optimizer_tool_found($opt, 'o')) {
						$optipng = $opt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng-custom') && !$optipng && !$use_system) {
				$opt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng-custom';
				$ewww_debug .= "found $opt, testing...<br>";
				if (filesize($opt) > 15000 && ewww_image_optimizer_mimetype($opt, 'b')) {
					$opt = ewww_image_optimizer_escapeshellcmd ( $opt );
					if (ewww_image_optimizer_tool_found($opt, 'o')) {
						$optipng = $opt;
					}
				}
			}
			if (!$optipng) {
				$optipng = ewww_image_optimizer_find_binary('optipng', 'o');
			}
		}
		if ($g) {
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle') && !$use_system) {
				$gpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
				$ewww_debug .= "found $gpt, testing...<br>";
				if (ewww_image_optimizer_md5check($gpt) && ewww_image_optimizer_mimetype($gpt, 'b')) {
					$gpt = ewww_image_optimizer_escapeshellcmd ( $gpt );
					if (ewww_image_optimizer_tool_found($gpt, 'g')) {
						$gifsicle = $gpt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle-custom') && !$gifsicle && !$use_system) {
				$gpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle-custom';
				$ewww_debug .= "found $gpt, testing...<br>";
				if (filesize($gpt) > 15000 && ewww_image_optimizer_mimetype($gpt, 'b')) {
					$gpt = ewww_image_optimizer_escapeshellcmd ( $gpt );
					if (ewww_image_optimizer_tool_found($gpt, 'g')) {
						$gifsicle = $gpt;
					}
				}
			}
			if (!$gifsicle) {
				$gifsicle = ewww_image_optimizer_find_binary('gifsicle', 'g');
			}
		}
		if ($p) {
			// pngout is special and has a dynamic and static binary to check
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static') && !$use_system) {
				$ppt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static';
				$ewww_debug .= "found $ppt, testing...<br>";
				if (ewww_image_optimizer_md5check($ppt) && ewww_image_optimizer_mimetype($ppt, 'b')) {
					$ppt = ewww_image_optimizer_escapeshellcmd ( $ppt );
					if (ewww_image_optimizer_tool_found($ppt, 'p')) {
						$pngout = $ppt;
					}
				}
			}
			if (!$pngout) {
				$pngout = ewww_image_optimizer_find_binary('pngout', 'p');
			}
			if (!$pngout) {
				$pngout = ewww_image_optimizer_find_binary('pngout-static', 'p');
			}
		}
		if ($q) {
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant') && !$use_system) {
				$qpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
				$ewww_debug .= "found $qpt, testing...<br>";
				if (ewww_image_optimizer_md5check($qpt) && ewww_image_optimizer_mimetype($qpt, 'b')) {
					$qpt = ewww_image_optimizer_escapeshellcmd ( $qpt );
					if (ewww_image_optimizer_tool_found($qpt, 'q')) {
						$pngquant = $qpt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant-custom') && !$pngquant && !$use_system) {
				$qpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant-custom';
				$ewww_debug .= "found $qpt, testing...<br>";
				if (filesize($qpt) > 15000 && ewww_image_optimizer_mimetype($qpt, 'b')) {
					$qpt = ewww_image_optimizer_escapeshellcmd ( $qpt );
					if (ewww_image_optimizer_tool_found($qpt, 'q')) {
						$pngquant = $qpt;
					}
				}
			}
			if (!$pngquant) {
				$pngquant = ewww_image_optimizer_find_binary('pngquant', 'q');
			}
		}
		if ($w) {
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp') && !$use_system) {
				$wpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
				$ewww_debug .= "found $wpt, testing...<br>";
				if (ewww_image_optimizer_md5check($wpt) && ewww_image_optimizer_mimetype($wpt, 'b')) {
					$wpt = ewww_image_optimizer_escapeshellcmd ( $wpt );
					if (ewww_image_optimizer_tool_found($wpt, 'w')) {
						$webp = $wpt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp-custom') && !$webp && !$use_system) {
				$wpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp-custom';
				$ewww_debug .= "found $wpt, testing...<br>";
				if (filesize($wpt) > 5000 && ewww_image_optimizer_mimetype($wpt, 'b')) {
					$wpt = ewww_image_optimizer_escapeshellcmd ( $wpt );
					if (ewww_image_optimizer_tool_found($wpt, 'w')) {
						$webp = $wpt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp-alt') && !$webp && !$use_system) {
				$wpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp-alt';
				$ewww_debug .= "found $wpt, testing...<br>";
				if (filesize($wpt) > 5000 && ewww_image_optimizer_mimetype($wpt, 'b')) {
					$wpt = ewww_image_optimizer_escapeshellcmd ( $wpt );
					if (ewww_image_optimizer_tool_found($wpt, 'w')) {
						$webp = $wpt;
					}
				}
			}
			if (!$webp) {
				$webp = ewww_image_optimizer_find_binary('cwebp', 'w');
			}
		}
	}
	if ($jpegtran) $ewww_debug .= "using: $jpegtran<br>";
	if ($optipng) $ewww_debug .= "using: $optipng<br>";
	if ($gifsicle) $ewww_debug .= "using: $gifsicle<br>";
	if ($pngout) $ewww_debug .= "using: $pngout<br>";
	if ($pngquant) $ewww_debug .= "using: $pngquant<br>";
	if ($webp) $ewww_debug .= "using: $webp<br>";
	ewwwio_memory( __FUNCTION__ );
	return array(
		'JPEGTRAN' => $jpegtran,
		'OPTIPNG' => $optipng,
		'GIFSICLE' => $gifsicle,
		'PNGOUT' => $pngout,
		'PNGQUANT' => $pngquant,
		'WEBP' => $webp,
	);
}

// checks the binary at $path against a list of valid md5sums
function ewww_image_optimizer_md5check($path) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_md5check()</b><br>";
	$ewww_debug .= "$path: " . md5_file($path) . "<br>";
	$valid_md5sums = array(
		//jpegtran
		'e2ba2985107600ebb43f85487258f6a3',
		'67c1dbeab941255a4b2b5a99db3c6ef5',
		'4a78fdeac123a16d2b9e93b6960e80b1',
		'a3f65d156a4901226cb91790771ca73f',
		'98cca712e6c162f399e85aec740bf560',
		'2dab67e5f223b70c43b2fef355b39d3f',
		'4da4092708650ceb79df19d528e7956b',
		'9d482b93d4129f7e87ce36c5e650de0c',
		'1c251658834162b01913702db0013c08',
		'dabf8173725e15d866f192f77d9e3883',
		'e4f7809c84a0722abe2b1d003c98a181',
		'a9f10ee88569bd7e72a7c2437ea9c09b', // jpegtran.exe
		'63d3edb0807ac1adbaefd437da8602ef', // jpegtran-sol
		'298e79ec273dec5dc77b98b1578dab23', // jpegtran-fbsd
		'4e87350ccff7c483e764d479ad4f84ea', // jpegtran-fbsd64
		'8e4a09bb04ba001f5f16651ae8594f7f', // jpegtran-linux
		'47c39feae0712f2996c61e5ae639b706', // jpegtran-linux64
		'9df8764bfe6a0434d436ed0cadbec8f0', // jpegtran-mac
		//optipng
		'4eb91937291ce5038d0c68f5f2edbcfd', // optipng-linux .7.4
		'899e3c569080a55bcc5de06a01c8e23a',
		'0467bd0c73473221d21afbc5275503e4',
		'293e26924a274c6185a06226619d8e02',
		'bcb27d22377f8abf3e9fe88a60030885',
		'8359078a649aeec2bd472ec84a4f39e1', // optipng-sol
		'aa20003676d1a3321032fa550a73716a', // optipng-fbsd
		'9a9e86346590878d23ef663086ffae2b', // optipng-mac
		'e3d154829ea57a0bdd88b080f6851265', // optipng.exe
		'31698da4f5ca00b35e910c77acae65bb', // optipng-linux
		//gifsicle
		'2384f770d307c42b9c1e53cdc8dd662d',
		'24fc5f33b33c0d11fb2e88f5a93949d0',
		'e4a14bce92755261fe21798c295d06db',
		'9ddef564fed446700a3a7303c39610a3',
		'aad47bafdb2bc8a9f0755f57f94d6eaf',
		'46360c01622ccb514e9e7ef1ac5398f0',
		'44273fad7b3fd1145bfcf35189648f66',
		'4568ef450ec9cd73bab55d661fb167ec',
		'f8d8baa175977a23108c84603dbfcc78',
		'3b592b6398dd7f379740c0b63e83825c',
		'ce935b38b1fd7b3c47d5de57f05c8cd2',
		'fe94420e6c49c29861b95ad93a3a4805',
		'151e395e2efa0e7845b18984d0f092af',
		'7ae972062cf3f99218057b055a4e1e9c',
		'c0bf45a291b93fd0a52318eddeaf5791',
		'ac8fa17a7004fa216242af2367d1a838', // gifsicle-sol
		'db1037b1e5e42108b48da564b8598610', // gifsicle-fbsd
		'58f42368e86a4910d101d37fee748409', // gifsicle-linux
		'39aca9edbb9495a241dc21fa678a09da', // gifsicle-mac
		'32a75a5122ff9b783ed7dd76d65f6297', // gifsicle.exe
		//pngout
		'2b62778559e31bc750dc2dcfd249be32', 
		'ea8655d1a1ef98833b294fb74f349c3e',
		'a30517e045076cab1bb5b5f3a57e999e',
		'6e60aafca40ecc0e648c442f83fa9688',
		'1882ae8efb503c4abdd0d18d974d5fa3',
		'aad1f8107955876efb0b0d686450e611',
		'991f9e7d2c39cb1f658684971d583468',
		'5de47b8cc0943eeceaf1683cb544b4a0',
		'c30de32f31259b79ffb13ca0d9d7a77d',
		'670a0924e9d042be2c60cd4f3ce1d975',
		'c77c5c870755e9732075036a548d8e61',
		'37cdbfcdedc9079f23847f0349efa11c',
		'8bfc5e0e6f0f964c7571988b0e9e2017',
		'b8ead81e0ed860d6461f67d60224ab7b',
		'f712daee5048d5d70197c5f339ac0b02',
		'e006b880f9532af2af0811515218bbd4',
		'b175b4439b054a61e8a41eca9a6e3505',
		'eabcbabde6c7c568e95afd73b7ed096e',
		//pngquant
		'6eff276339f9ad818eecd347a5fa99e2',
		'79d8c4f5ff2dbb36068c3e3de42fdb1e',
		'90ea1271c54ce010afba478c5830a75f',
		'3ad57a9c3c9093d65664f3260f44df60',
		'5d480fbe88ab5f732dbc5c9d8b76c2fd', // solaris
		'6fd8b12b542b9145d79925942551dbc8', // pngquant.exe
		'b3bbc013acc8bc04d3b531809abdadbb', // pngquant-sol
		'323246b9300362be24320dc72ba51af4', // pngquant-fbsd
		'46bb066d676bf94cbfd78bdc0227e74e', // pngquant-linux
		'3b94673f48a92cf034eb0095611966da', // pngquant-mac
		//cwebp
		'085ea7844800980c72fa30835d6f6044', // cwebp.exe
		'4610c239ba00d515701c75e90efe5534', // cwebp-sol
		'44acd143a8dac72bbf5795a10d96da98', // cwebp-fbsd
		'038b5acbbcd43e6811850be7d51236de', // cwebp-fbsd64
		'9429dd850cc2f976961de5fe61f05e97', // cwebp-linux6
		'eb3a5b6eae54140269ed6dcf6f792d37', // cwebp-linux664
		'62272b2bd33218664b2355f516b6e8fc', // cwebp-linux8
		'9b6f13ce6ee5a028cbd2765e2d53a1d7', // cwebp-linux864
		'd43bf5eed775695d5ecfe4eafcbd7af7', // cwebp-mac8
		'dab793f82cf6a3830898c75410583154', // cwebp-mac7
		);
	foreach ($valid_md5sums as $md5_sum) {
		if ($md5_sum == md5_file($path)) {
			$ewww_debug .= 'md5sum verified, binary is intact<br>';
			ewwwio_memory( __FUNCTION__ );
			return TRUE;
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return FALSE;
}

// check the mimetype of the given file ($path) with various methods
// valid values for $type are 'b' for binary or 'i' for image
function ewww_image_optimizer_mimetype($path, $case) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_mimetype()</b><br>";
	$ewww_debug .= "testing mimetype: $path <br>";
	if ( $case == 'i' && preg_match( '/^RIFF.+WEBPVP8/', file_get_contents( $path, NULL, NULL, 0, 16 ) ) ) {
			return 'image/webp';
	}
	if (function_exists('finfo_file') && defined('FILEINFO_MIME')) {
		// create a finfo resource
		$finfo = finfo_open(FILEINFO_MIME);
		// retrieve the mimetype
		$type = explode(';', finfo_file($finfo, $path));
		$type = $type[0];
		finfo_close($finfo);
		$ewww_debug .= "finfo_file: $type <br>";
	}
	// see if we can use the getimagesize function
	if (empty($type) && function_exists('getimagesize') && $case === 'i') {
		// run getimagesize on the file
		$type = getimagesize($path);
		// make sure we have results
		if(false !== $type){
			// store the mime-type
			$type = $type['mime'];
		}
		$ewww_debug .= "getimagesize: $type <br>";
	}
	// see if we can use mime_content_type
	if (empty($type) && function_exists('mime_content_type')) {
		// retrieve and store the mime-type
		$type = mime_content_type($path);
		$ewww_debug .= "mime_content_type: $type <br>";
	}
	// if nothing else has worked, try the 'file' command
	if ((empty($type) || $type != 'application/x-executable') && $case == 'b') {
		// find the 'file' command
		if ($file = ewww_image_optimizer_find_binary('file', 'f')) {
			// run 'file' on the file in question
			exec("$file $path", $filetype);
			$ewww_debug .= "file command: $filetype[0] <br>";
			// if we've found a proper binary
			if ((strpos($filetype[0], 'ELF') && strpos($filetype[0], 'executable')) || strpos($filetype[0], 'Mach-O universal binary')) {
				$type = 'application/x-executable';
			}
		}
	}
	// if we are dealing with a binary, and found an executable
	if ($case == 'b' && preg_match('/executable|octet-stream/', $type)) {
	ewwwio_memory( __FUNCTION__ );
		return $type;
	// otherwise, if we are dealing with an image
	} elseif ($case == 'i') {
	ewwwio_memory( __FUNCTION__ );
		return $type;
	// if all else fails, bail
	} else {
	ewwwio_memory( __FUNCTION__ );
		return false;
	}
}

// escape any spaces in the filename, not sure any more than that is necessary for unixy systems
function ewww_image_optimizer_escapeshellcmd ($path) {
	return (preg_replace('/ /', '\ ', $path));
}

// test the given path ($path) to see if it returns a valid version string
// returns: version string if found, FALSE if not
function ewww_image_optimizer_tool_found($path, $tool) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_tool_found()</b><br>";
	$ewww_debug .= "testing case: $tool at $path<br>";
	switch($tool) {
		case 'j': // jpegtran
			exec($path . ' -v ' . EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'sample.jpg 2>&1', $jpegtran_version);
			if (!empty($jpegtran_version)) $ewww_debug .= "$path: $jpegtran_version[0]<br>";
			foreach ($jpegtran_version as $jout) { 
				if (preg_match('/Independent JPEG Group/', $jout)) {
					$ewww_debug .= 'optimizer found<br>';
					return $jout;
				}
			}
			break;
		case 'o': // optipng
			exec($path . ' -v 2>&1', $optipng_version);
			if (!empty($optipng_version)) $ewww_debug .= "$path: $optipng_version[0]<br>";
			if (!empty($optipng_version) && strpos($optipng_version[0], 'OptiPNG') === 0) {
				$ewww_debug .= 'optimizer found<br>';
				return $optipng_version[0];
			}
			break;
		case 'g': // gifsicle
			exec($path . ' --version 2>&1', $gifsicle_version);
			if (!empty($gifsicle_version)) $ewww_debug .= "$path: $gifsicle_version[0]<br>";
			if (!empty($gifsicle_version) && strpos($gifsicle_version[0], 'LCDF Gifsicle') === 0) {
				$ewww_debug .= 'optimizer found<br>';
				return $gifsicle_version[0];
			}
			break;
		case 'p': // pngout
			exec("$path 2>&1", $pngout_version);
			if (!empty($pngout_version)) $ewww_debug .= "$path: $pngout_version[0]<br>";
			if (!empty($pngout_version) && strpos($pngout_version[0], 'PNGOUT') === 0) {
				$ewww_debug .= 'optimizer found<br>';
				return $pngout_version[0];
			}
			break;
		case 'q': // pngquant
			exec($path . ' -V 2>&1', $pngquant_version);
			if ( ! empty( $pngquant_version ) ) $ewww_debug .= "$path: $pngquant_version[0]<br>";
			if ( ! empty( $pngquant_version ) && substr( $pngquant_version[0], 0, 3 ) >= 2.0 ) {
			//if (!empty($pngquant_version) && strpos($pngquant_version[0], '2.0') === 0) {
				$ewww_debug .= 'optimizer found<br>';
				return $pngquant_version[0];
			}
			break;
		case 'i': // ImageMagick
			exec("$path -version 2>&1", $convert_version);
			if (!empty($convert_version)) $ewww_debug .= "$path: $convert_version[0]<br>";
			if (!empty($convert_version) && strpos($convert_version[0], 'ImageMagick')) {
				$ewww_debug .= 'imagemagick found<br>';
				return $convert_version[0];
			}
			break;
		case 'f': // file
			exec("$path -v 2>&1", $file_version);
			if (!empty($file_version[1])) $ewww_debug .= "$path: $file_version[1]<br>";
			if (!empty($file_version[1]) && preg_match('/magic/', $file_version[1])) {
				$ewww_debug .= 'file binary found<br>';
				return $file_version[0];
			} elseif (!empty($file_version[1]) && preg_match('/usage: file/', $file_version[1])) {
				$ewww_debug .= 'file binary found<br>';
				return $file_version[0];
			}
			break;
		case 'n': // nice
			exec("$path 2>&1", $nice_output);
			if (isset($nice_output)) $ewww_debug .= "$path: $nice_output[0]<br>";
			if (isset($nice_output) && preg_match('/usage/', $nice_output[0])) {
				$ewww_debug .= 'nice found<br>';
				return TRUE;
			} elseif (isset($nice_output) && preg_match('/^\d+$/', $nice_output[0])) {
				$ewww_debug .= 'nice found<br>';
				return TRUE;
			}
			break;
		case 't': // tar
			exec("$path --version 2>&1", $tar_version);
			if (!empty($tar_version[0])) $ewww_debug .= "$path: $tar_version[0]<br>";
			if (!empty($tar_version[0]) && preg_match('/bsdtar/', $tar_version[0])) {
				$ewww_debug .= 'tar found<br>';
				return $tar_version[0];
			} elseif (!empty($tar_version[0]) && preg_match('/GNU tar/i', $tar_version[0])) {
				$ewww_debug .= 'tar found<br>';
				return $tar_version[0];
			}
			break;
		case 'w': //cwebp
			exec("$path -version 2>&1", $webp_version);
			if ( !empty( $webp_version ) ) $ewww_debug .= "$path: $webp_version[0]<br>";
			if ( !empty( $webp_version ) && preg_match( '/0.4.\d/', $webp_version[0] ) ) {
				$ewww_debug .= 'optimizer found<br>';
				return $webp_version[0];
			}
			break;
	}
	$ewww_debug .= 'tool not found <br>';
	ewwwio_memory( __FUNCTION__ );
	return FALSE;
}

// searches system paths for the given $binary and passes along the $switch
function ewww_image_optimizer_find_binary ($binary, $switch) {
	if (ewww_image_optimizer_tool_found($binary, $switch)) {
		return $binary;
	} elseif (ewww_image_optimizer_tool_found('/usr/bin/' . $binary, $switch)) {
		return '/usr/bin/' . $binary;
	} elseif (ewww_image_optimizer_tool_found('/usr/local/bin/' . $binary, $switch)) {
		return '/usr/local/bin/' . $binary;
	} elseif (ewww_image_optimizer_tool_found('/usr/gnu/bin/' . $binary, $switch)) {
		return '/usr/gnu/bin/' . $binary;
	} elseif (ewww_image_optimizer_tool_found('/usr/syno/bin/' . $binary, $switch)) { // for synology diskstation OS
		return '/usr/syno/bin/' . $binary;
	} else {
		return '';
	}
}

/**
 * Process an image.
 *
 * Returns an array of the $file, $results, $converted to tell us if an image changes formats, and the $original file if it did.
 *
 * @param   string $file		Full absolute path to the image file
 * @param   int $gallery_type		1=wordpress, 2=nextgen, 3=flagallery, 4=aux_images, 5=image editor, 6=imagestore, 7=retina
 * @param   boolean $converted		tells us if this is a resize and the full image was converted to a new format
 * @param   boolean $new		tells the optimizer that this is a new image, so it should attempt conversion regardless of previous results
 * @param   boolean $fullsize		tells the optimizer this is a full size image
 * @returns array
 */
function ewww_image_optimizer($file, $gallery_type = 4, $converted = false, $new = false, $fullsize = false) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer()</b><br>";
	// if the plugin gets here without initializing, we need to run through some things first
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_CLOUD' ) ) {
		ewww_image_optimizer_init();
	}
	$bypass_optimization = apply_filters( 'ewww_image_optimizer_bypass', false, $file );
	if (true === $bypass_optimization) {
		// tell the user optimization was skipped
		$msg = __( "Optimization skipped", EWWW_IMAGE_OPTIMIZER_DOMAIN );
		$ewww_debug .= "optimization bypassed: $file <br>";
		// send back the above message
		return array(false, $msg, $converted, $file);
	}
	// initialize the original filename 
	$original = $file;
	$result = '';
	// check that the file exists
	if (FALSE === file_exists($file)) {
		// tell the user we couldn't find the file
		$msg = sprintf(__("Could not find <span class='code'>%s</span>", EWWW_IMAGE_OPTIMIZER_DOMAIN), $file);
		$ewww_debug .= "file doesn't appear to exist: $file <br>";
		// send back the above message
		return array(false, $msg, $converted, $original);
	}
	// check that the file is writable
	if ( FALSE === is_writable($file) ) {
		// tell the user we can't write to the file
		$msg = sprintf(__("<span class='code'>%s</span> is not writable", EWWW_IMAGE_OPTIMIZER_DOMAIN), $file);
		$ewww_debug .= "couldn't write to the file $file<br>";
		// send back the above message
		return array(false, $msg, $converted, $original);
	}
	if (function_exists('fileperms'))
		$file_perms = substr(sprintf('%o', fileperms($file)), -4);
	$file_owner = 'unknown';
	$file_group = 'unknown';
	if (function_exists('posix_getpwuid')) {
		$file_owner = posix_getpwuid(fileowner($file));
		$file_owner = $file_owner['name'];
	}
	if (function_exists('posix_getgrgid')) {
		$file_group = posix_getgrgid(filegroup($file));
		$file_group = $file_group['name'];
	}
	$ewww_debug .= "permissions: $file_perms, owner: $file_owner, group: $file_group <br>";
	$type = ewww_image_optimizer_mimetype($file, 'i');
	if (!$type) {
		//otherwise we store an error message since we couldn't get the mime-type
		$msg = __('Missing finfo_file(), getimagesize() and mime_content_type() PHP functions', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		$ewww_debug .= "couldn't find any functions for mimetype detection<br>";
		return array(false, $msg, $converted, $original);
	}
	if (!EWWW_IMAGE_OPTIMIZER_CLOUD) {
		// check to see if 'nice' exists
		$nice = ewww_image_optimizer_find_binary('nice', 'n');
	}
	// if the user has disabled the utility checks
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_skip_check') == TRUE || EWWW_IMAGE_OPTIMIZER_CLOUD) {
		$skip_jpegtran_check = true;
		$skip_optipng_check = true;
		$skip_gifsicle_check = true;
		$skip_pngout_check = true;
	} else {
		// otherwise we set the variables to false
		$skip_jpegtran_check = false;
		$skip_optipng_check = false;
		$skip_gifsicle_check = false;
		$skip_pngout_check = false;
	}
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
		$skip_jpegtran_check = true;
	}	
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png')) {
		$skip_optipng_check = true;
		$skip_pngout_check = true;
	}	
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif')) {
		$skip_gifsicle_check = true;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_metadata_skip_full' ) && $fullsize ) {
		$keep_metadata = true;
	} else {
		$keep_metadata = false;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_lossy_skip_full' ) && $fullsize ) {
		$skip_lossy = true;
	} else {
		$skip_lossy = false;
	}
	// if the full-size image was converted
	if ($converted) {
		$ewww_debug .= "full-size image was converted, need to rebuild filename for meta<br>";
		$filenum = $converted;
		// grab the file extension
		preg_match('/\.\w+$/', $file, $fileext);
		// strip the file extension
		$filename = str_replace($fileext[0], '', $file);
		// grab the dimensions
		preg_match('/-\d+x\d+(-\d+)*$/', $filename, $fileresize);
		// strip the dimensions
		$filename = str_replace($fileresize[0], '', $filename);
		// reconstruct the filename with the same increment (stored in $converted) as the full version
		$refile = $filename . '-' . $filenum . $fileresize[0] . $fileext[0];
		// rename the file
		rename($file, $refile);
		$ewww_debug .= "moved $file to $refile<br>";
		// and set $file to the new filename
		$file = $refile;
		$original = $file;
	}
	// get the original image size
	$orig_size = filesize($file);
	$ewww_debug .= "original filesize: $orig_size<br>";
	if ( $orig_size < ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_size' ) ) {
		// tell the user optimization was skipped
		$msg = __( "Optimization skipped", EWWW_IMAGE_OPTIMIZER_DOMAIN );
		$ewww_debug .= "optimization bypassed due to filesize: $file <br>";
		// send back the above message
		return array(false, $msg, $converted, $file);
	}
	if ( $type == 'image/png' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_png_size' ) && $orig_size > ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_png_size' ) ) {
		// tell the user optimization was skipped
		$msg = __( "Optimization skipped", EWWW_IMAGE_OPTIMIZER_DOMAIN );
		$ewww_debug .= "optimization bypassed due to filesize: $file <br>";
		// send back the above message
		return array($file, $msg, $converted, $file);
	}
	// initialize $new_size with the original size, HOW ABOUT A ZERO...
	//$new_size = $orig_size;
	$new_size = 0;
	// set the optimization process to OFF
	$optimize = false;
	// toggle the convert process to ON
	$convert = true;
	// run the appropriate optimization/conversion for the mime-type
	switch($type) {
		case 'image/jpeg':
			$png_size = 0;
			// if jpg2png conversion is enabled, and this image is in the wordpress media library
			if ((ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_to_png') && $gallery_type == 1) || !empty($_GET['ewww_convert'])) {
				// generate the filename for a PNG
				// if this is a resize version
				if ($converted) {
					// just change the file extension
					$pngfile = preg_replace('/\.\w+$/', '.png', $file);
				// if this is a full size image
				} else {
					// get a unique filename for the png image
					list($pngfile, $filenum) = ewww_image_optimizer_unique_filename($file, '.png');
				}
			} else {
				// otherwise, set it to OFF
				$convert = false;
				$pngfile = '';
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['ewww_force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $pngfile, 'image/png', $skip_lossy);
				if ($converted) {
					$converted = $filenum;
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/png', null );
				} else {
					ewww_image_optimizer_webp_create( $file, $new_size, $type, null );
				}
				break;
			}
			if ($convert) {
				$tools = ewww_image_optimizer_path_check(true, true, false, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			} else {
				$tools = ewww_image_optimizer_path_check(true, false, false, false, false, ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			}
			// if jpegtran optimization is disabled
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_jpegtran')) {
				// store an appropriate message in $result
				$result = sprintf(__('%s is disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'jpegtran');
			// otherwise, if we aren't skipping the utility verification and jpegtran doesn't exist
			} elseif (!$skip_jpegtran_check && !$tools['JPEGTRAN']) {
				// store an appropriate message in $result
				$result = sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>jpegtran</em>');
			// otherwise, things should be good, so...
			} else {
				// set the optimization process to ON
				$optimize = true;
			}
			// if optimization is turned ON
			if ($optimize && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
				$ewww_debug .= "attempting to optimize JPG...<br>";
				// generate temporary file-names:
				$tempfile = $file . ".tmp"; //non-progressive jpeg
				$progfile = $file . ".prog"; // progressive jpeg
				// check to see if we are supposed to strip metadata (badly named)
				if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && ! $keep_metadata){
					// don't copy metadata
					$copy_opt = 'none';
				} else {
					// copy all the metadata
					$copy_opt = 'all';
				}
				// run jpegtran - non-progressive
				exec( "$nice " . $tools['JPEGTRAN'] . " -copy $copy_opt -optimize -outfile " . ewww_image_optimizer_escapeshellarg( $tempfile ) . " " . ewww_image_optimizer_escapeshellarg( $file ) );
				// run jpegtran - progressive
				exec( "$nice " . $tools['JPEGTRAN'] . " -copy $copy_opt -optimize -progressive -outfile " . ewww_image_optimizer_escapeshellarg( $progfile ) . " " . ewww_image_optimizer_escapeshellarg( $file ) );
				if (is_file($tempfile)) {
					// check the filesize of the non-progressive JPG
					$non_size = filesize($tempfile);
				} else {
					$non_size = 0;
				}
				if (is_file($progfile)) {
					// check the filesize of the progressive JPG
					$prog_size = filesize($progfile);
				} else {
					$prog_size = 0;
				}
				$ewww_debug .= "optimized JPG (non-progresive) size: $non_size<br>";
				$ewww_debug .= "optimized JPG (progresive) size: $prog_size<br>";
				if ($non_size === false || $prog_size === false) {
					$result = __('Unable to write file', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$new_size = 0;
				} elseif (!$non_size || !$prog_size) {
					$result = __('Optimization failed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$new_size = 0;
				} else {
					// if the progressive file is bigger
					if ($prog_size > $non_size) {
						// store the size of the non-progessive JPG
						$new_size = $non_size;
						if (is_file($progfile)) {
							// delete the progressive file
							unlink($progfile);
						}
					// if the progressive file is smaller or the same
					} else {
						// store the size of the progressive JPG
						$new_size = $prog_size;
						// replace the non-progressive with the progressive file
						rename($progfile, $tempfile);
					}
				}
				$ewww_debug .= "optimized JPG size: $new_size<br>";
				// if the best-optimized is smaller than the original JPG, and we didn't create an empty JPG
				if ($orig_size > $new_size && $new_size != 0) {
					// replace the original with the optimized file
					rename($tempfile, $file);
					// store the results of the optimization
					$result = "$orig_size vs. $new_size";
				// if the optimization didn't produce a smaller JPG
				} else {
					if (is_file($tempfile)) {
						// delete the optimized file
						unlink($tempfile);
					}
					// store the results
					$result = 'unchanged';
					$new_size = $orig_size;
				}
			// if conversion and optimization are both turned OFF, finish the JPG processing
			} elseif (!$convert) {
				ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tools['WEBP'] );
				break;
			}
			// if the conversion process is turned ON, or if this is a resize and the full-size was converted
			if ($convert && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
				$ewww_debug .= "attempting to convert JPG to PNG: $pngfile <br>";
				if (empty($new_size)) {
					$new_size = $orig_size;
				}
				// retrieve version info for ImageMagick
				$convert_path = ewww_image_optimizer_find_binary('convert', 'i');
				// convert the JPG to PNG
				if (!empty($convert_path)) {
					$ewww_debug .= "converting with ImageMagick<br>";
					exec( $convert_path . " " . ewww_image_optimizer_escapeshellarg( $file ) . " -strip " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
				} elseif (ewww_image_optimizer_gd_support()) {
					$ewww_debug .= "converting with GD<br>";
					imagepng(imagecreatefromjpeg($file), $pngfile);
				}
				// if lossy optimization is ON and full-size exclusion is not active
				if (ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy') && $tools['PNGQUANT'] && !$skip_lossy ) {
					$ewww_debug .= "attempting lossy reduction<br>";
					exec( "$nice " . $tools['PNGQUANT'] . " " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					$quantfile = preg_replace('/\.\w+$/', '-fs8.png', $pngfile);
					if ( file_exists( $quantfile ) && filesize( $pngfile ) > filesize( $quantfile ) ) {
						$ewww_debug .= "lossy reduction is better: original - " . filesize( $pngfile ) . " vs. lossy - " . filesize( $quantfile ) . "<br>";
						rename( $quantfile, $pngfile );
					} elseif ( file_exists( $quantfile ) ) {
						$ewww_debug .= "lossy reduction is worse: original - " . filesize( $pngfile ) . " vs. lossy - " . filesize( $quantfile ) . "<br>";
						unlink( $quantfile );
					} else {
						$ewww_debug .= "pngquant did not produce any output<br>";
					}
				}
				// if optipng isn't disabled
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng')) {
					// retrieve the optipng optimization level
					$optipng_level = ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level');
					if (ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && preg_match( '/0.7/', ewww_image_optimizer_tool_found( $tools['OPTIPNG'], 'o' ) ) && ! $keep_metadata ) {
						$strip = '-strip all ';
					} else {
						$strip = '';
					}
					// if the PNG file was created
					if (file_exists($pngfile)) {
						$ewww_debug .= "optimizing converted PNG with optipng<br>";
						// run optipng on the new PNG
						exec( "$nice " . $tools['OPTIPNG'] . " -o$optipng_level -quiet $strip " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					}
				}
				// if pngout isn't disabled
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout')) {
					// retrieve the pngout optimization level
					$pngout_level = ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level');
					// if the PNG file was created
					if (file_exists($pngfile)) {
						$ewww_debug .= "optimizing converted PNG with pngout<br>";
						// run pngout on the new PNG
						exec( "$nice " . $tools['PNGOUT'] . " -s$pngout_level -q " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					}
				}
				if (is_file($pngfile)) {
					// find out the size of the new PNG file
					$png_size = filesize($pngfile);
				} else {
					$png_size = 0;
				}
				$ewww_debug .= "converted PNG size: $png_size<br>";
				// if the PNG is smaller than the original JPG, and we didn't end up with an empty file
				if ($new_size > $png_size && $png_size != 0) {
					$ewww_debug .= "converted PNG is better: $png_size vs. $new_size<br>";
					// store the size of the converted PNG
					$new_size = $png_size;
					// check to see if the user wants the originals deleted
					if (ewww_image_optimizer_get_option('ewww_image_optimizer_delete_originals') == TRUE) {
						// delete the original JPG
						unlink($file);
					}
					// store the location of the PNG file
					$file = $pngfile;
					// let webp know what we're dealing with now
					$type = 'image/png';
					// successful conversion and we store the increment
					$converted = $filenum;
				} else {
					$ewww_debug .= "converted PNG is no good<br>";
					// otherwise delete the PNG
					$converted = FALSE;
					if (is_file($pngfile)) {
						unlink ($pngfile);
					}
				}
			}
			ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'] );
			break;
		case 'image/png':
			// png2jpg conversion is turned on, and the image is in the wordpress media library
			if ( ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_to_jpg' ) || ! empty( $_GET['ewww_convert'] ) ) && $gallery_type == 1 && ! $skip_lossy ) {
				$ewww_debug .= "PNG to JPG conversion turned on<br>";
				// if the user set a fill background for transparency
				$background = '';
				if ($background = ewww_image_optimizer_jpg_background()) {
					// set background color for GD
					$r = hexdec('0x' . strtoupper(substr($background, 0, 2)));
                                        $g = hexdec('0x' . strtoupper(substr($background, 2, 2)));
					$b = hexdec('0x' . strtoupper(substr($background, 4, 2)));
					// set the background flag for 'convert'
					$background = "-background " . '"' . "#$background" . '"';
				} else {
					$r = '';
					$g = '';
					$b = '';
				}
				// if the user manually set the JPG quality
				if ($quality = ewww_image_optimizer_jpg_quality()) {
					// set the quality for GD
					$gquality = $quality;
					// set the quality flag for 'convert'
					$cquality = "-quality $quality";
				} else {
					$cquality = '';
					$gquality = '92';
				}
				// if this is a resize version
				if ($converted) {
					// just replace the file extension with a .jpg
					$jpgfile = preg_replace('/\.\w+$/', '.jpg', $file);
				// if this is a full version
				} else {
					// construct the filename for the new JPG
					list($jpgfile, $filenum) = ewww_image_optimizer_unique_filename($file, '.jpg');
				}
			} else {
				$ewww_debug .= "PNG to JPG conversion turned off<br>";
				// turn the conversion process OFF
				$convert = false;
				$jpgfile = '';
				$r = null;
				$g = null;
				$b = null;
				$gquality = null;
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['ewww_force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $jpgfile, 'image/jpeg', $skip_lossy, array('r' => $r, 'g' => $g, 'b' => $b, 'quality' => $gquality));
				if ($converted) {
					$converted = $filenum;
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/jpeg', null );
				} else {
					ewww_image_optimizer_webp_create( $file, $new_size, $type, null );
				}
				break;
			}
			if ($convert) {
				$tools = ewww_image_optimizer_path_check(true, true, false, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			} else {
				$tools = ewww_image_optimizer_path_check(false, true, false, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			}
			// if pngout and optipng are disabled
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') && ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout')) {
				// tell the user all PNG tools are disabled
				$result = __('png tools are disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN);
			// if the utility checking is on, optipng is enabled, but optipng cannot be found
			} elseif (!$skip_optipng_check && !$tools['OPTIPNG'] && !ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng')) {
				// tell the user optipng is missing
				$result = sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>optipng</em>');
			// if the utility checking is on, pngout is enabled, but pngout cannot be found
			} elseif (!$skip_pngout_check && !$tools['PNGOUT'] && !ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout')) {
				// tell the user pngout is missing
				$result = sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>pngout</em>');
			} else {
				// turn optimization on if we made it through all the checks
				$optimize = true;
			}
			// if optimization is turned on
			if ($optimize) {
				// if lossy optimization is ON and full-size exclusion is not active
				if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ) && $tools['PNGQUANT'] && ! $skip_lossy ) {
					$ewww_debug .= "attempting lossy reduction<br>";
					exec( "$nice " . $tools['PNGQUANT'] . " " . ewww_image_optimizer_escapeshellarg( $file ) );
					$quantfile = preg_replace( '/\.\w+$/', '-fs8.png', $file );
					if ( file_exists( $quantfile ) && filesize( $file ) > filesize( $quantfile ) ) {
						$ewww_debug .= "lossy reduction is better: original - " . filesize( $file ) . " vs. lossy - " . filesize( $quantfile ) . "<br>";
						rename($quantfile, $file);
					} elseif ( file_exists( $quantfile ) ) {
						$ewww_debug .= "lossy reduction is worse: original - " . filesize( $file ) . " vs. lossy - " . filesize( $quantfile ) . "<br>";
						unlink($quantfile);
					} else {
						$ewww_debug .= "pngquant did not produce any output<br>";
					}
				}
				// if optipng is enabled
				if(!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng')) {
					// retrieve the optimization level for optipng
					$optipng_level = ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level');
					if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && preg_match( '/0.7/', ewww_image_optimizer_tool_found( $tools['OPTIPNG'], 'o' ) ) && ! $keep_metadata ) {
						$strip = '-strip all ';
					} else {
						$strip = '';
					}
					// run optipng on the PNG file
					exec( "$nice " . $tools['OPTIPNG'] . " -o$optipng_level -quiet $strip " . ewww_image_optimizer_escapeshellarg( $file ) );
				}
				// if pngout is enabled
				if(!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout')) {
					// retrieve the optimization level for pngout
					$pngout_level = ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level');
					// run pngout on the PNG file
					exec( "$nice " . $tools['PNGOUT'] . " -s$pngout_level -q " . ewww_image_optimizer_escapeshellarg( $file ) );
				}
			// if conversion and optimization are both disabled we are done here
			} elseif (!$convert) {
				$ewww_debug .= "calling webp, but neither convert or optimize<br>";
				ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tools['WEBP'] );
				break;
			}
			// flush the cache for filesize
			clearstatcache();
			// retrieve the new filesize of the PNG
			$new_size = filesize($file);
			// if conversion is on and the PNG doesn't have transparency or the user set a background color to replace transparency
			if ($convert && (!ewww_image_optimizer_png_alpha($file) || ewww_image_optimizer_jpg_background())) {
				$ewww_debug .= "attempting to convert PNG to JPG: $jpgfile <br>";
				if (empty($new_size)) {
					$new_size = $orig_size;
				}
				// retrieve version info for ImageMagick
				$convert_path = ewww_image_optimizer_find_binary('convert', 'i');
				// convert the PNG to a JPG with all the proper options
				if (!empty($convert_path)) {
					$ewww_debug .= "converting with ImageMagick<br>";
					$ewww_debug .= "using command: $convert_path $background -flatten $cquality $file $jpgfile";
					exec ( "$convert_path $background -flatten $cquality " . ewww_image_optimizer_escapeshellarg( $file ) . " " . ewww_image_optimizer_escapeshellarg( $jpgfile ) );
				} elseif (ewww_image_optimizer_gd_support()) {
					$ewww_debug .= "converting with GD<br>";
					// retrieve the data from the PNG
					$input = imagecreatefrompng($file);
					// retrieve the dimensions of the PNG
					list($width, $height) = getimagesize($file);
					// create a new image with those dimensions
					$output = imagecreatetruecolor($width, $height);
					if ($r === '') {
						$r = 255;
						$g = 255;
						$b = 255;
					}
					// allocate the background color
					$rgb = imagecolorallocate($output, $r, $g, $b);
					// fill the new image with the background color 
					imagefilledrectangle($output, 0, 0, $width, $height, $rgb);
					// copy the original image to the new image
					imagecopy($output, $input, 0, 0, 0, 0, $width, $height);
					// output the JPG with the quality setting
					imagejpeg($output, $jpgfile, $gquality);
				}
				if (is_file($jpgfile)) {
					// retrieve the filesize of the new JPG
					$jpg_size = filesize($jpgfile);
					$ewww_debug .= "converted JPG filesize: $jpg_size<br>";
				} else {
					$jpg_size = 0;
					$ewww_debug .= "unable to convert to JPG<br>";
				}
				// next we need to optimize that JPG if jpegtran is enabled
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_jpegtran' ) && file_exists( $jpgfile ) ) {
					// generate temporary file-names:
					$tempfile = $jpgfile . ".tmp"; //non-progressive jpeg
					$progfile = $jpgfile . ".prog"; // progressive jpeg
					// check to see if we are supposed to strip metadata (badly named)
					if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && ! $keep_metadata ){
						// don't copy metadata
						$copy_opt = 'none';
					} else {
						// copy all the metadata
						$copy_opt = 'all';
					}
					// run jpegtran - non-progressive
					exec( "$nice " . $tools['JPEGTRAN'] . " -copy $copy_opt -optimize -outfile " . ewww_image_optimizer_escapeshellarg( $tempfile ) . " " . ewww_image_optimizer_escapeshellarg( $jpgfile ) );
					// run jpegtran - progressive
					exec( "$nice " . $tools['JPEGTRAN'] . " -copy $copy_opt -optimize -progressive -outfile " . ewww_image_optimizer_escapeshellarg( $progfile ) . " " . ewww_image_optimizer_escapeshellarg( $jpgfile ) );
					if (is_file($tempfile)) {
						// check the filesize of the non-progressive JPG
						$non_size = filesize($tempfile);
						$ewww_debug .= "non-progressive JPG filesize: $non_size<br>";
					} else {
						$non_size = 0;
					}
					if (is_file($progfile)) {
						// check the filesize of the progressive JPG
						$prog_size = filesize($progfile);
						$ewww_debug .= "progressive JPG filesize: $prog_size<br>";
					} else {
						$prog_size = 0;
					}
					// if the progressive file is bigger
					if ($prog_size > $non_size) {
						// store the size of the non-progessive JPG
						$opt_jpg_size = $non_size;
						if (is_file($progfile)) {
							// delete the progressive file
							unlink($progfile);
						}
						$ewww_debug .= "keeping non-progressive JPG<br>";
					// if the progressive file is smaller or the same
					} else {
						// store the size of the progressive JPG
						$opt_jpg_size = $prog_size;
						// replace the non-progressive with the progressive file
						rename($progfile, $tempfile);
						$ewww_debug .= "keeping progressive JPG<br>";
					}
					// if the best-optimized is smaller than the original JPG, and we didn't create an empty JPG
					if ($jpg_size > $opt_jpg_size && $opt_jpg_size != 0) {
						// replace the original with the optimized file
						rename($tempfile, $jpgfile);
						// store the size of the optimized JPG
						$jpg_size = $opt_jpg_size;
						$ewww_debug .= "optimized JPG was smaller than un-optimized version<br>";
					// if the optimization didn't produce a smaller JPG
					} elseif (is_file($tempfile)) {
						// delete the optimized file
						unlink($tempfile);
					}
				} 
				$ewww_debug .= "converted JPG size: $jpg_size<br>";
				// if the new JPG is smaller than the original PNG
				if ($new_size > $jpg_size && $jpg_size != 0) {
					// store the size of the JPG as the new filesize
					$new_size = $jpg_size;
					// if the user wants originals delted after a conversion
					if (ewww_image_optimizer_get_option('ewww_image_optimizer_delete_originals') == TRUE) {
						// delete the original PNG
						unlink($file);
					}
					// update the $file location to the new JPG
					$file = $jpgfile;
					// let webp know what we're dealing with now
					$type = 'image/jpeg';
					// successful conversion, so we store the increment
					$converted = $filenum;
				} else {
					$converted = FALSE;
					if (is_file($jpgfile)) {
						// otherwise delete the new JPG
						unlink ($jpgfile);
					}
				}
			}
			ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'] );
			break;
		case 'image/gif':
			// if gif2png is turned on, and the image is in the wordpress media library
			if ((ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') && $gallery_type == 1) || !empty($_GET['ewww_convert'])) {
				// generate the filename for a PNG
				// if this is a resize version
				if ($converted) {
					// just change the file extension
					$pngfile = preg_replace('/\.\w+$/', '.png', $file);
				// if this is the full version
				} else {
					// construct the filename for the new PNG
					list($pngfile, $filenum) = ewww_image_optimizer_unique_filename($file, '.png');
				}
			} else {
				// turn conversion OFF
				$convert = false;
				$pngfile = '';
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['ewww_force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $pngfile, 'image/png', $skip_lossy);
				if ($converted) {
					$converted = $filenum;
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/png', null ); 
 				}
				break;
			}
			if ($convert) {
				$tools = ewww_image_optimizer_path_check(false, true, true, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			} else {
				$tools = ewww_image_optimizer_path_check(false, false, true, false, false, false);
			}
			// if gifsicle is disabled
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_gifsicle')) {
				// return an appropriate message
				$result = sprintf(__('%s is disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'gifsicle');
			// if utility checking is on, and gifsicle is not installed
			} elseif (!$skip_gifsicle_check && !$tools['GIFSICLE']) {
				// return an appropriate message
				$result = sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>gifsicle</em>');
			} else {
				// otherwise, turn optimization ON
				$optimize = true;
			}
			// if optimization is turned ON
			if ($optimize) {
				$tempfile = $file . ".tmp"; //temporary GIF output
				// run gifsicle on the GIF
				exec( "$nice " . $tools['GIFSICLE'] . " -b -O3 --careful -o $tempfile " . ewww_image_optimizer_escapeshellarg( $file ) );
				if (file_exists($tempfile)) {
					// retrieve the filesize of the temporary GIF
					$new_size = filesize($tempfile);
					// if the new GIF is smaller
					if ($orig_size > $new_size && $new_size != 0) {
						// replace the original with the optimized file
						rename($tempfile, $file);
						// store the results of the optimization
						$result = "$orig_size vs. $new_size";
					// if the optimization didn't produce a smaller GIF
					} else {
						if (is_file($tempfile)) {
							// delete the optimized file
							unlink($tempfile);
						}
						// store the results
						$result = 'unchanged';
						$new_size = $orig_size;
					}
				}
			// if conversion and optimization are both turned OFF, we are done here
			} elseif (!$convert) {
				break;
			}
			// flush the cache for filesize
			clearstatcache();
			// get the new filesize for the GIF
			$new_size = filesize($file);
			// if conversion is ON and the GIF isn't animated
			if ($convert && !ewww_image_optimizer_is_animated($file)) {
				if (empty($new_size)) {
					$new_size = $orig_size;
				}
				// if optipng is enabled
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') && $tools['OPTIPNG']) {
					// retrieve the optipng optimization level
					$optipng_level = ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level');
					if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && preg_match( '/0.7/', ewww_image_optimizer_tool_found( $tools['OPTIPNG'], 'o' ) ) && ! $keep_metadata ) {
						$strip = '-strip all ';
					} else {
						$strip = '';
					}
					// run optipng on the GIF file
					exec( "$nice " . $tools['OPTIPNG'] . " -out " . ewww_image_optimizer_escapeshellarg( $pngfile ) . " -o$optipng_level -quiet $strip " . ewww_image_optimizer_escapeshellarg( $file ) );
				}
				// if pngout is enabled
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') && $tools['PNGOUT']) {
					// retrieve the pngout optimization level
					$pngout_level = ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level');
					// if $pngfile exists (which means optipng was run already)
					if (file_exists($pngfile)) {
						// run pngout on the PNG file
						exec( "$nice " . $tools['PNGOUT'] . " -s$pngout_level -q " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					} else {
						// run pngout on the GIF file
						exec( "$nice " . $tools['PNGOUT'] . " -s$pngout_level -q " . ewww_image_optimizer_escapeshellarg( $file ) . " " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					}
				}
				// if a PNG file was created
				if (file_exists($pngfile)) {
					// retrieve the filesize of the PNG
					$png_size = filesize($pngfile);
					// if the new PNG is smaller than the original GIF
					if ($new_size > $png_size && $png_size != 0) {
						// store the PNG size as the new filesize
						$new_size = $png_size;
						// if the user wants original GIFs deleted after successful conversion
						if (ewww_image_optimizer_get_option('ewww_image_optimizer_delete_originals') == TRUE) {
							// delete the original GIF
							unlink($file);
						}
						// update the $file location with the new PNG
						$file = $pngfile;
						// let webp know what we're dealing with now
						$type = 'image/png';
						// normally this would be at the end of the section, but we only want to do webp if the image was successfully converted to a png
						ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'] );
						// successful conversion (for now), so we store the increment
						$converted = $filenum;
					} else {
						$converted = FALSE;
						if (is_file($pngfile)) {
							unlink ($pngfile);
						}
					}
				}
			}
			break;
		default:
			// if not a JPG, PNG, or GIF, tell the user we don't work with strangers
			return array($file, __('Unknown type: ' . $type, EWWW_IMAGE_OPTIMIZER_DOMAIN), $converted, $original);
	}
	// if their cloud api license limit has been exceeded
	if ($result == 'exceeded') {
		return array($file, __('License exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN), $converted, $original);
	}
	if (!empty($new_size)) {
		$results_msg = ewww_image_optimizer_update_table ($file, $new_size, $orig_size, $new);
	ewwwio_memory( __FUNCTION__ );
		return array($file, $results_msg, $converted, $original);
	}
	ewwwio_memory( __FUNCTION__ );
	// otherwise, send back the filename, the results (some sort of error message), the $converted flag, and the name of the original image
	return array($file, $result, $converted, $original);
}

// creates webp images alongside JPG and PNG files
// needs a filename, the filesize, mimetype, and the path to the cwebp binary
function ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tool ) {
	// TODO: do something to handle webp error output
	global $ewww_debug;
	$ewww_debug .= '<b>ewww_image_optimizer_webp_create()</b><br>';
	// change the file extension
	$webpfile = $file . '.webp';
	if ( file_exists( $webpfile ) || ! ewww_image_optimizer_get_option('ewww_image_optimizer_webp') ) {
		return;
	}
	if ( empty( $tool ) ) {
		ewww_image_optimizer_cloud_optimizer($file, $type, false, $webpfile, 'image/webp');
	} else {
		// check to see if 'nice' exists
		$nice = ewww_image_optimizer_find_binary('nice', 'n');
		switch($type) {
			case 'image/jpeg':
				exec( "$nice " . $tool . " -q  85 -quiet " . ewww_image_optimizer_escapeshellarg( $file ) . " -o " . ewww_image_optimizer_escapeshellarg( $webpfile ) . ' 2>&1', $cli_output );
				break;
			case 'image/png':
				exec( "$nice " . $tool . " -lossless -quiet " . ewww_image_optimizer_escapeshellarg( $file ) . " -o " . ewww_image_optimizer_escapeshellarg( $webpfile ) . ' 2>&1', $cli_output );
				break;
		}
	}
	if ( file_exists( $webpfile ) && $orig_size < filesize( $webpfile ) ) {
		$ewww_debug .= 'webp file was too big, deleting<br>';
		unlink( $webpfile );
	}
	ewwwio_memory( __FUNCTION__ );
}

// retrieves the pngout linux package with wget, unpacks it with tar, 
// copies the appropriate version to the plugin folder, and sends the user back where they came from
function ewww_image_optimizer_install_pngout() {
	global $ewww_debug;
	$ewww_debug .= '<b>ewww_image_optimizer_install_pngout()</b><br>';
	$permissions = apply_filters( 'ewww_image_optimizer_admin_permissions', '' );
	if ( FALSE === current_user_can( $permissions ) ) {
		wp_die(__('You don\'t have permission to install image optimizer utilities.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	}
	if (PHP_OS != 'WINNT') {
		$tar = ewww_image_optimizer_find_binary('tar', 't');
	}
	if (empty($tar) && PHP_OS != 'WINNT') $pngout_error = __('tar command not found', EWWW_IMAGE_OPTIMIZER_DOMAIN);
	if (PHP_OS == 'Linux') {
		$os_string = 'linux';
	}
	if (PHP_OS == 'FreeBSD') {
		$os_string = 'bsd';
	}
	$latest = '20130221';
	if (empty($pngout_error)) {
		if (PHP_OS == 'Linux' || PHP_OS == 'FreeBSD') {
			$download_result = ewww_image_optimizer_escapeshellarg ( download_url ( 'http://static.jonof.id.au/dl/kenutils/pngout-' . $latest . '-' . $os_string . '-static.tar.gz' ) );
			if (is_wp_error($download_result)) {
				$pngout_error = $download_result->get_error_message();
			} else {
				$arch_type = php_uname('m');
				exec("$tar xzf $download_result -C " . ewww_image_optimizer_escapeshellarg ( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH ) . ' pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static');
				if (!rename(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static', EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static'))
					if (empty($pngout_error)) $pngout_error = __("could not move pngout", EWWW_IMAGE_OPTIMIZER_DOMAIN);
				if (!chmod(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static', 0755))
					if (empty($pngout_error)) $pngout_error = __("could not set permissions", EWWW_IMAGE_OPTIMIZER_DOMAIN);
				$pngout_version = ewww_image_optimizer_tool_found ( ewww_image_optimizer_escapeshellarg ( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) . 'pngout-static', 'p' );
			}
		}
		if (PHP_OS == 'Darwin') {
			$download_result = ewww_image_optimizer_escapeshellarg ( download_url ( 'http://static.jonof.id.au/dl/kenutils/pngout-' . $latest . '-darwin.tar.gz' ) );
			if (is_wp_error($download_result)) {
				$pngout_error = $download_result->get_error_message();
			} else {
				exec("$tar xzf $download_result -C " . ewww_image_optimizer_escapeshellarg ( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH ) . ' pngout-' . $latest . '-darwin/pngout');
				if (!rename(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngout-' . $latest . '-darwin/pngout', EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static'))
					if (empty($pngout_error)) $pngout_error = __("could not move pngout", EWWW_IMAGE_OPTIMIZER_DOMAIN);
				if (!chmod(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static', 0755))
					if (empty($pngout_error)) $pngout_error = __("could not set permissions", EWWW_IMAGE_OPTIMIZER_DOMAIN);
				$pngout_version = ewww_image_optimizer_tool_found( ewww_image_optimizer_escapeshellarg ( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) . 'pngout-static', 'p' );
			}
		}
	}
	if (PHP_OS == 'WINNT') {
		$download_result = download_url('http://advsys.net/ken/util/pngout.exe');
		if (is_wp_error($download_result)) {
			$pngout_error = $download_result->get_error_message();
		} else {
			if (!rename($download_result, EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout.exe'))
				if (empty($pngout_error)) $pngout_error = __("could not move pngout", EWWW_IMAGE_OPTIMIZER_DOMAIN);
			$pngout_version = ewww_image_optimizer_tool_found ( '"' . EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout.exe"', 'p' );
		}
	}
	if (!empty($pngout_version)) {
		$sendback = add_query_arg('ewww_pngout', 'success', remove_query_arg(array('ewww_pngout', 'ewww_error'), wp_get_referer()));
	}
	if (!isset($sendback)) {
		$sendback = add_query_arg(array('ewww_pngout' => 'failed', 'ewww_error' => urlencode($pngout_error)), remove_query_arg(array('ewww_pngout', 'ewww_error'), wp_get_referer()));
	}
	wp_redirect($sendback);
	ewwwio_memory( __FUNCTION__ );
	exit(0);
}
