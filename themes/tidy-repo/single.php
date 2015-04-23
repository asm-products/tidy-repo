<?php
/**
 * The Template for displaying all single posts.
 *
 * @package tidy repo
 */

get_header(); ?>

<div class="g ad-g">

	<?php while ( have_posts() ) : the_post(); ?>

		<?php get_template_part( 'content', 'single' ); ?>

	<?php endwhile; // end of the loop. ?>

</div>

<?php get_footer(); ?>