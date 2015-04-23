<?php
/**
 * The main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package tidy repo
 */

get_header();

if ( have_posts() ) : 

if(is_category()) { ?>
	
	<header class="page-header">
		<h1 class="cat-header"><?php single_cat_title( '', true ); ?> WordPress Plugins</h1>
	</header><!-- .page-header -->

<?php } 

	if (is_author()) { ?>
	<header class="page-header">
		<h1 class="cat-header">Posts by <?php echo get_the_author(); ?></h1>
	</header><!-- .page-header -->

<?php	}

	 if( is_front_page()  && !is_paged() ) { ?>

		<header class="page-header">
			<h1><?php printf( __( 'This week\'s plugin reviews', 'tidy_repo' )); ?></h1>
		</header><!-- .page-header -->

		<div class="g">
			
		<?php the_post(); ?>

			<div class="u-1-2 papa-full p-box">
				<?php get_template_part( 'content', get_post_format() ); ?>
			</div>

		<?php the_post(); ?>

			<div class="u-1-2 papa-full p-box">
				<?php get_template_part( 'content', get_post_format() ); ?>
			</div>

		</div>

		<div class="callout g">
			<div class="u-7-12 papa-full">
				<h4 class="cllout--title">Subscribe to the Tidy Repo Newsletter</h4>
				<p>Get the latest news, tips and deals from Tidy Repo, every week. We promise not to pester you.</p>
			</div>
			<div class="cf u-5-12 papa-full">
			<?php echo do_shortcode('[mc4wp_form]'); ?>
			</div>
		</div>
		
		<header class="section-header">
			<h2><?php printf( __( 'Recent plugin reviews', 'tidy_repo' )); ?></h2>
		</header><!-- .page-header -->

	<?php } ?>

	<div class="g">

		<?php
		$i = 1;
		while ( have_posts() ) : the_post();
		$i++; ?>

		<div class="u-1-3 papa-half baby-full p-box">
			<?php get_template_part( 'content', get_post_format() ); ?>
		</div>

		<?php
		if($i === 3) { ?>
		<div class="asmo-box u-1-3 baby-full">

		<?php
			if (is_active_sidebar( 'ad-sidebar' )) {
				dynamic_sidebar( 'ad-sidebar' );
			} ?>

		</div>

		<?php
		}
			
		endwhile;

		else : 
			 get_template_part( 'no-results', 'index' ); 
		endif; ?>

	</div>

	<div class="pagination">
		<?php
			global $wp_query;
			$total_pages = $wp_query->max_num_pages;

			if ($total_pages > 1){
				$current_page = max(1, get_query_var('paged'));

				echo paginate_links(array(
					'base' => get_pagenum_link(1) . '%_%',
					'format' => 'page/%#%',
					'current' => $current_page,
					'total' => $total_pages,
				));
			}
		?>
	</div>

<?php get_footer(); ?>