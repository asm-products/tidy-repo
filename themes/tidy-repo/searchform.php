<?php
/**
 * The template for displaying search forms in tidy repo
 *
 * @package tidy repo
 */
?>

<form method="get" id="searchform" class="" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
	<label class="search--label" for="s"><?php _ex( 'Plugin Search', 'tidy_repo' ); ?></label>
	<input type="search" class="u-3-5" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" id="s">

	<button class="btn-search">
		<span class="visuallyhidden">Search</span>
		<svg viewBox="0 0 100 100" class="icon shape-searchbutton">
			<use xlink:href="#shape-search"></use>
		</svg>
	</button>
</form>