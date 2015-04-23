<?php
/**
 * @package tidy repo
 */
?>

<div class="ad-g-u ad-u-left">

<?php 	

	$thumb_transient = 'responsive_post_thumb-' . get_the_ID();
	$thumbnail = get_transient($thumb_transient);

	if($thumbnail === false) {
		$thumbnail = get_responsive_post_thumbnail();
		set_transient($thumb_transient, $thumbnail, 0);
	}

	echo $thumbnail; ?>

	<div class="block-content">
		<h1 class="txtcenter"><?php the_title(); ?></h1>

		<p class="author">Plugin Author: <?php the_field('author_name'); ?></p>
		<!-- <h6 class="corner"><?php the_date(); ?></h6> -->

		<div class="meta">
			<p class="meta--author">
				<svg viewBox="0 0 100 100" class="icon shape-author">
					<use xlink:href="#shape-author"></use>
				</svg>
				
				<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>"><?php the_author_meta( 'display_name' ); ?></a>
			</p>

			<p class="meta--date">
				<svg viewBox="0 0 100 100" class="icon shape-calendar">
					<use xlink:href="#shape-calendar"></use>
				</svg>

				<?php echo get_the_date(); ?>
			</p>

			<p class="meta--categories">
				<svg viewBox="0 0 100 100" class="icon shape-meta-category">
					<use xlink:href="#shape-category"></use>
				</svg>

				<?php
					$wpcats = wp_get_post_categories( $post->ID );
					$cats = array();
			 			foreach ($wpcats as $c) {
							$catname = get_cat_name( $c );
							$catlink = get_category_link( $c );
							$cats[] = '<a href="' . $catlink . '">' . $catname . '</a>';
						}
					echo implode(', ', $cats);
				?>
			</p>
		</div>

		<div class="social">
			<span class="scl--share">Share this post:</span>

			<a rel="external nofollow" class="scl--item scl--facebook" href="http://www.facebook.com/sharer/sharer.php?s=100&p[url]=<?php the_permalink(); ?>&p[title]=<?php the_title(); ?>" target="_blank" >
				<svg viewBox="0 0 100 100" class="icon shape-scl">
					<use xlink:href="#shape-facebook"></use>
				</svg>

				<span>Facebook</span>
			</a>

			<a rel="external nofollow" class="scl--item scl--twitter" href="http://twitter.com/intent/tweet/?text=<?php the_title(); ?>&url=<?php the_permalink(); ?>&via=TidyRepo" target="_blank">
				<svg viewBox="0 0 100 100" class="icon shape-scl">
					<use xlink:href="#shape-twitter"></use>
				</svg>

				<span>Twitter</span>
			</a>
		</div>

		<article>
			<?php the_content();

			if (get_field('plugin_type') !== "Free") { ?>
				<a class="lst-info__link" href="<?php the_field('website'); ?>" target="_blank">Download <?php the_title(); ?> Now!</a>
			<?php } ?>
		</article>

		<div class="social">
			<span class="scl--share">Share this post:</span>

			<a rel="external nofollow" class="scl--item scl--facebook" href="http://www.facebook.com/sharer/sharer.php?s=100&p[url]=<?php the_permalink(); ?>&p[title]=<?php the_title(); ?>" target="_blank" >
				<svg viewBox="0 0 100 100" class="icon shape-scl">
					<use xlink:href="#shape-facebook"></use>
				</svg>

				<span class="">Facebook</span>
			</a>

			<a rel="external nofollow" class="scl--item scl--twitter" href="http://twitter.com/intent/tweet/?text=<?php the_title(); ?>&url=<?php the_permalink(); ?>&via=@TidyRepo" target="_blank">
				<svg viewBox="0 0 100 100" class="icon shape-scl">
					<use xlink:href="#shape-twitter"></use>
				</svg>

				<span class="">Twitter</span>
			</a>
		</div>

		<?php rp4wp_children(); ?>


		<div>
			<h4>Did you like this post?</h4>
			
			<?php
				// If comments are open or we have at least one comment, load up the comment template
				if ( comments_open() || '0' != get_comments_number() )
					comments_template();
			?>
		</div>
	</div>
</div>

<div class="ad-u-right ad-g-u r-box">

	<?php 
	if (get_field('plugin_type') !== "Paid") {
		// Need to work out some smart caching here
		$website = get_field('website');

		if ($website) {
			$slug = basename(get_field('website'));
			if (($plugin_info = get_transient('plugin_info_' . $slug)) == false) {
				$args = (object) array(
			        'slug' => $slug,
			        'fields' => array(
			            'sections' => false,
			            'tags' => false,
			            'description' => false,
			            'tested' => false,
			            'requires' => false
			        )
			    );

				$request = array( 'action' => 'plugin_information', 'timeout' => 15, 'request' => serialize( $args) );

				$url = 'http://api.wordpress.org/plugins/info/1.0/';

				$response = wp_remote_post( $url, array( 'body' => $request ) );

				$plugin_info = unserialize( $response['body'] );

				if($plugin_info) {
					set_transient('plugin_info_' . $slug, $plugin_info, 60*60*24);
				} else {
					set_transient('plugin_info_' . $slug, 'false', 60*60*24);
				}
			}

			if ($plugin_info !== 'false' && $plugin_info) { ?>
			 	<div class="widget">
			 		<h5>About this Plugin</h5>

			 		<ul class="lst-info list">
			 			<li><strong>Downloaded:</strong> <?php echo($plugin_info->downloaded); ?> times</li>

			 			<li><strong>Rating:</strong> 
			 				<div class="rating-bar">
			 					<span class="rb--fill" style="width: <?php echo($plugin_info->rating); ?>%"></span>
			 				</div>
			 			</li>

			 			<li><strong>Last Updated:</strong> <?php echo date("m-d-Y", strtotime($plugin_info->last_updated)); ?></li>

			 			<li><a class="lst-info__link" href="<?php the_field('website'); ?>">Download this Plugin</a></li>
			 		</ul>
			 	</div>
			<?php }
		}
	}
	?>

	<div id="md-large-widget" class="widget">
		<style type="text/css"> #md-large-widget-content{ display:inline-block; text-align:left;} .md-wg-deal-image {width:100%; margin-bottom: 5px;}  .md-wg-timer-block { font-size: 11px; color:#727a80; margin-top: 5px;}  .mdWidgetDigit {margin-right: 1px; display:inline-block;}  .mdWidgetWord {margin-right: 5px; display:inline-block;}  .md-wg-deal-link {font-weight: bold; font-size:15px; color:#57207c; text-decoration: none; line-height: 17px;} .md-wg-deal-link:hover {color:#f05028;}</style>
	<?php
		if (is_active_sidebar( 'ad-sidebar' ) ) {
			dynamic_sidebar( 'ad-sidebar' );
		} ?>
	</div>

	<div class="widget">
		<h5>Get the latest</h5>
			<p>Signup for our newsletter to get plugin tips, and see what's coming down the pipe.</p>
			<?php echo do_shortcode('[mc4wp_form]'); ?>
	</div>

	<div class="widget">
		<h5>Plugin Categories</h5>

		<form name="catdd" class="catdd">
			<span class="dwnarrow"></span>
			<select class="catslct" name="catslct" onChange="location=document.catdd.catslct.options[document.catdd.catslct.selectedIndex].value;" value="GO">
				<option selected>-</option>

				<?php
					global $categories; 
				    foreach($categories as $category) { ?>
				        <option value="<?php echo get_category_link( $category->term_id); ?>">
				            <?php echo $category->name; ?>
				        </option>
				    <?php }
				?>
			</select>
		</form>
	</div>

	<div class="widget cf">
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

	<div class="widget ft-nav">
		<h5>Follow Us</h5>

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