<?php
/**
 * The template used for displaying page content in page.php
 *
 * @package tidy repo
 */
?>

<div class="u-3-4 papa-full">
	<div class="block-content">
		<h2 class="txtcenter"><?php the_title(); ?></h3>
		<?php the_content(); ?>
	</div>
</div>

<div class="u-1-4 r-box papa-full">
	<div id="md-large-widget" class="widget">
		<style type="text/css"> #md-large-widget-content{ display:inline-block; text-align:left;} .md-wg-deal-image {width:100%; margin-bottom: 5px;}  .md-wg-timer-block { font-size: 11px; color:#727a80; margin-top: 5px;}  .mdWidgetDigit {margin-right: 1px; display:inline-block;}  .mdWidgetWord {margin-right: 5px; display:inline-block;}  .md-wg-deal-link {font-weight: bold; font-size:15px; color:#57207c; text-decoration: none; line-height: 17px;} .md-wg-deal-link:hover {color:#f05028;}</style>
	<?php
		if (is_active_sidebar( 'ad-sidebar' ) ) {
			dynamic_sidebar( 'ad-sidebar' );
		} ?>
	</div>

	<div class="widget">
		<h5>Plugin Categories</h5>

		<form name="catdd" class="catdd">
			<span class="dwnarrow"></span>
			<select class="catslct" name="catslct" onChange="location=document.catdd.catslct.options[document.catdd.catslct.selectedIndex].value;" value="GO">
				<option selected>-</option>
				<?php
					global $categories; 
				    foreach($categories as $category) {  ?>
				        <option value="<?php echo get_category_link( $category->term_id); ?>">
				            <?php echo $category->name; ?>
				        </option>
				    <?php }
				?> 
			</select>
		</form>
	</div>

	<div class="widget">

		<h5>Featured Posts</h5>

		<?php
				$featuredArgs = array(
					'post_type' => 'post',
					'posts_per_page' => 2
					);
				$featured_query = new WP_Query( $featuredArgs );

			if ( $featured_query->have_posts() ) {
				while ( $featured_query->have_posts() ) {
					$featured_query->the_post(); ?>

					<div class="ft--item">
						<a href="<?php the_permalink(); ?>">
							<?php
								echo get_the_post_thumbnail($post_id, 'smallest');
								echo '<span class="visuallyhidden">' . get_the_title() . '</span>';
							?>
						</a>
					</div>
				<?php }
			}
		?>
	</div>

	<div class="ft-nav">
		<a href="https://twitter.com/tidyrepo">
		    <svg viewBox="0 0 100 100" class="icon shape-twitter">
		    	<use xlink:href="#shape-twitter"></use>
		    </svg>

		    <span class="visuallyhidden">Twitter</span>
		</a>

		<a href="https://www.facebook.com/tidyrepo">
		    <svg viewBox="0 0 100 100" class="icon shape-facebook">
		    	<use xlink:href="#shape-facebook"></use>
		    	<span class="visuallyhidden">Facebook</span>
		    </svg>
		</a>

		<a href="http://feed.tidyrepo.com/">
		    <svg viewBox="0 0 100 100" class="icon shape-rss">
		    	<use xlink:href="#shape-rss"></use>
		    </svg>

		    <span class="visuallyhidden">RSS</span>
		</a>
	</div>
</div>