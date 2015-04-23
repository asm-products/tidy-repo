<?php
/**
 * The template for displaying search results pages.
 *
 */
get_header(); ?>

<?php if ( have_posts() ) : ?>

	<header class="page-header">
		<h1><?php printf( __( 'Search Results for: %s', 'tidy_repo' ), '<span class="make-main">' . get_search_query() . '</span>' ); ?></h1>
	</header><!-- .page-header -->

	<div class="g">

	<?php 
	$i = 1;
	while ( have_posts() ) : the_post();
	$i++; ?>

	<div class="u-1-3 papa-full p-box">
		<?php get_template_part( 'content' ); ?>
	</div>

	<?php
	if($i === 3) { ?>
	<div id="md-large-widget" class="ad-box u-1-3 baby-full">
		<style type="text/css"> #md-large-widget-content{ display:inline-block; font-family:Arial; text-align:left;} .md-wg-deal-image {width:100%; margin-bottom: 5px;}  .md-wg-timer-block { font-size: 11px; color:#727a80; margin-top: 5px;}  .mdWidgetDigit {margin-right: 1px; display:inline-block;}  .mdWidgetWord {margin-right: 5px; display:inline-block;}  .md-wg-deal-link {font-weight: bold; font-size:15px; color:#57207c; text-decoration: none; line-height: 17px;} .md-wg-deal-link:hover {color:#f05028;}</style>


	<?php
		if (is_active_sidebar( 'ad-sidebar' )) {
			dynamic_sidebar( 'ad-sidebar' );
		} ?>

		<span class="ribbon ad-ribbon">Advertisement</span>
	</div>

	<?php
	}

	endwhile; ?>
	</div>

	<div class="pagination">
	<?php
		global $wp_query;
		$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;
		$big = 999999999;

		echo paginate_links( array(
	      'base' => @add_query_arg('paged','%#%'),
	      'format' => '?paged=%#%',
	      'current' => $current,
	      'total' => $wp_query->max_num_pages,
	      'prev_next'    => false
		) );
	?>
	</div>

<?php else : ?>

	<?php get_template_part( 'no-results', 'index' ); ?>

<?php endif; ?>

<?php get_footer(); ?>