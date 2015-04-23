<?php
/**
 * @package tidy repo
 */

if (get_field('plugin_type') == "Paid") { ?>
	<span class="ribbon">Paid Plugin</span>
<?php } else if(get_field('plugin_type') == "Freemium") { ?>
	<span class="ribbon">Freemium</span>
<?php } ?>

<a href="<?php the_permalink(); ?>"><?php // echo picturefill_wp_apply_to_html(get_the_post_thumbnail($post_id, 'full', array( 'class' => 'sizes-homepage-sizes'))); ?>
<?php 
	$thumb_transient = 'responsive_home_thumb-' . get_the_ID();
	$thumbnail = get_transient($thumb_transient);

	if($thumbnail === false) {
		$thumbnail = get_responsive_home_thumbnail();
		set_transient($thumb_transient, $thumbnail, 0);
	}

	echo $thumbnail;
 ?>

<div class="block-grid">
	<h3 class="make-dark"><?php the_title(); ?></a></h3>
	<p><?php the_field('short_description'); ?></p>
</div>

<p class="bg--caption">
	<svg viewBox="0 0 100 100" class="icon shape-category">
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