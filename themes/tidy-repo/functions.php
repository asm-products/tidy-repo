<?php
/**
 * tidy repo functions and definitions
 *
 * @package tidy repo
 */

if ( ! function_exists( 'tidy_repo_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.
 */
function tidy_repo_setup() {

	/**
	 * Add default posts and comments RSS feed links to head
	 */
	add_theme_support( 'automatic-feed-links' );


	/**
	 * Enable support for Post Thumbnails on posts and pages
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );
}
endif; // tidy_repo_setup
add_action( 'after_setup_theme', 'tidy_repo_setup' );

function cc_mime_types( $mimes ){
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter( 'upload_mimes', 'cc_mime_types' );

add_filter( 'wpseo_use_page_analysis', '__return_false' );

// add ie conditional html5 shim to header
function add_ie_html5_shim () {
    echo '<!--[if lt IE 9]>';
    echo '<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>';
    echo '<![endif]-->';
}
add_action('wp_head', 'add_ie_html5_shim');

/**
 * Enqueue scripts and styles - Adding Versioning to stylesheets
 */
function tidy_repo_scripts() {
		wp_enqueue_style( 'style-name', get_stylesheet_uri(), '', '0.8' );
		wp_deregister_script('picturefill');
		wp_enqueue_script('picturefill-theme', get_stylesheet_directory_uri() . '/js/picturefill.min.js', '', '2.20', 'true');
}
add_action( 'wp_enqueue_scripts', 'tidy_repo_scripts' );

/**
 * Tames DISQUS comments so that it only outputs JS on specified
 * pages in the site.
 */
function tgm_tame_disqus_comments() {

	// If we are viewing a single post, we need the code, so return early.
	if ( is_single() ) {
		return;
	}
		
	// Tame Disqus from outputting JS on pages where comments are not available.
	remove_action( 'loop_end', 'dsq_loop_end' );
	remove_action( 'wp_footer', 'dsq_output_footer_comment_js' );

}
add_action( 'wp_head', 'tgm_tame_disqus_comments' );

function my_custom_post_status(){
	register_post_status( 'ready-to-go', array(
		'label'                     => _x( 'Ready-To-Go', 'post' ),
		'public'                    => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Ready To Go <span class="count">(%s)</span>', 'Ready To Go <span class="count">(%s)</span>' ),
	) );
}
add_action( 'init', 'my_custom_post_status' );

// Add New Image Size for Responsive Images
add_image_size('smallest', 400);

function register_respsonive_attrs(){
  picturefill_wp_register_srcset('full-srcset', array('smallest', 'medium', 'large', 'full'), 'full');
  picturefill_wp_register_sizes('full-sizes', '(min-width: 780px) 60vw, 90vw', 'full');
  picturefill_wp_register_sizes('homepage-sizes', '(min-width: 951px) 30vw, 80vw');
}
 
add_filter('picturefill_wp_register_srcset', 'register_respsonive_attrs');

// Adds a New Image Template For Picturefill - Fixes RSS Images
function new_template_path() {
	return get_stylesheet_directory() . '/inc/templates/';
}
add_filter('picturefill_wp_template_path', 'new_template_path');

//Removes width in caption output
add_shortcode('wp_caption', 'fixed_img_caption_shortcode');
add_shortcode('caption', 'fixed_img_caption_shortcode');


function fixed_img_caption_shortcode($attr, $content = null) {
	if ( ! isset( $attr['caption'] ) ) {
		if ( preg_match( '#((?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?)(.*)#is', $content, $matches ) ) {
			$content = $matches[1];
			$attr['caption'] = trim( $matches[2] );
		}
	}
	$output = apply_filters('img_caption_shortcode', '', $attr, $content);
	if ( $output != '' )
		return $output;
	extract(shortcode_atts(array(
		'id'	=> '',
		'align'	=> 'alignnone',
		'width'	=> '',
		'caption' => ''
	), $attr));
	if ( 1 > (int) $width || empty($caption) )
		return $content;
	if ( $id ) $id = 'id="' . esc_attr($id) . '" ';
	return '<div ' . $id . 'class="wp-caption ' . esc_attr($align) . '" >' . do_shortcode( $content ) . '<p class="wp-caption-text">' . $caption . '</p></div>';
}

function get_responsive_home_thumbnail() {
    $image_small  = wp_get_attachment_image_src( get_post_thumbnail_id(), 'smallest' );
    $image_medium = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium' );
    $image_large  = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
    $image_full  = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );

    return'
	<img class="wp-post-image"
	sizes="(min-width: 951px) 30vw, 90vw" 
	srcset=" ' . esc_url($image_small[0]) . ' 400w, ' . esc_url($image_medium[0]) .' 600w, '. esc_url($image_large[0]) . ' 900w, ' . esc_url($image_full[0]) . ' 1280w">';
}

function get_responsive_post_thumbnail() {
	    $image_small  = wp_get_attachment_image_src( get_post_thumbnail_id(), 'smallest' );
	    $image_medium = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium' );
	    $image_large  = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
	    $image_full  = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );

	    return'
		<img class="wp-post-image"
		sizes="(min-width: 780px) 60vw, 90vw" 
		srcset=" ' . esc_url($image_small[0]) . ' 400w, ' . esc_url($image_medium[0]) .' 600w, '. esc_url($image_large[0]) . ' 900w, ' . esc_url($image_full[0]) . ' 1280w">';

}

function delete_transient_thumb($id) {
	$transient = 'responsive_thumb-' . $id;
	if($transient !== false) {
		delete_transient($transient);
	}
}
add_action('pre_post_update', 'delete_transient_thumb');


$categories = get_transient('all_categories');
if($categories === false) {
	$catargs = array (
	    'hiearchical' => 0,
	);
	$categories = get_categories( $catargs );
	set_transient('all_categories', $categories, 0);
}


function sidebar_widget_init() {

	register_sidebar( array(
		'name' => 'Ad Widget',
		'id' => 'ad-sidebar',
		'before_widget' => '<div>',
		'after_widget' => '</div>',
		'before_title' => '<h5 class="widget">',
		'after_title' => '</h5>',
	) );
}
add_action( 'widgets_init', 'sidebar_widget_init' );

add_filter( 'rp4wp_append_content', '__return_false' );
