<?php
/**
 * The template for displaying 404 pages (Not Found).
 *
 * @package tidy repo
 */

get_header(); ?>

	<article>
		<header>
			<h1><?php _e( 'Oops! That page can&rsquo;t be found.', 'tidy_repo' ); ?></h1>
		</header>

		<div>
			<p><?php _e( 'It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'tidy_repo' ); ?></p>

			<?php get_search_form(); ?>
		</div>
	</article>

<?php get_footer(); ?>