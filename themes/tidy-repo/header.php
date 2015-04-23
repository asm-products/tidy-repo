<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package tidy repo
 */
?><!DOCTYPE html>

<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width">

  <title><?php wp_title( '|', true, 'right' ); ?></title>

  <link rel="profile" href="http://gmpg.org/xfn/11" />
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <link rel="author" href="https://plus.google.com/118248971542277377915/posts">

  <?php wp_head(); 

    // Google Universal Analytics
    if (!current_user_can('read')) { ?>
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-42183707-1', 'auto');
        ga('require', 'displayfeatures');
        ga('send', 'pageview');
    </script>
    <?php }
  ?>
</head>

<body <?php body_class(); ?>>
  <?php include_once("svgs/svg-defs.svg"); ?>
  
  <header class="header cf">
    <div class="header--wrap">
      <div class="header--title">
        <h1>
          <svg viewBox="0 0 100 100" class="icon shape-plugin">
            <use xlink:href="#shape-admin-plugins"></use>
          </svg>

          <a href="<?php echo home_url(); ?>">TIDY REPO</a>
        </h1>

        <p class="tagline">The best and most reliable WordPress plugins</p>
      </div>
        
      <button id="js-menuBtn" class="btn-nav">Menu</button>

      <nav id="js-menu" class="top-nav">
        <a class="first nav--link <?php if ( is_home() ) { echo 'current_page_item'; } ?>" href="<?php echo home_url(); ?>/">Home</a>
        <ul class="lst-drop">
          <p class="nav--link nav--drop <?php if ( is_single() || is_archive() ) { echo 'current_page_item'; } ?>">Plugin Reviews</p>
          <?php global $categories; ?>

          <ul class="list lst-triple lst-nav">

              <?php 
                foreach($categories as $category) { 
                  $catID = $category->cat_ID; ?>

                  <li class="list__item">
                      <a <?php if($catID == get_query_var('cat')) { echo 'class="current_page_item"'; } ?> href="<?php echo get_category_link( $catID); ?>"><?php echo $category->name; ?></a>
                  </li>
                <?php }
              ?>
          </ul>
        </ul>
        <ul class="lst-drop">
          <p class="nav--link nav--drop <?php if ( is_page('suggest-plugin') || is_page('feedback') ) { echo 'current_page_item'; } ?>" href="<?php echo home_url(); ?>/suggest-plugin/">Contact</p>
          
          <ul class="list lst-triple lst-nav">
            <li><a href="<?php echo home_url(); ?>/suggest-plugin/">Suggest a Plugin</a></li>
            <li><a href="<?php echo home_url(); ?>/suggest-plugin/feedback">Feedback</a></li>
          </ul>
        </ul>

        <a class="nav--link special" href="<?php echo get_permalink( get_page_by_path( 'find-plugin-service' ) ); ?>">Help Me Find a Plugin</a>

        <a id="js-searchBtn" class="nav--link" href="#search"><span class="visuallyhidden">Search</span>
          <svg viewBox="0 0 100 100" class="icon shape-search">
            <use xlink:href="#shape-search"></use>
          </svg>
        </a>
      </nav>
    </div>
  </header>

  <div id="js-search" class="bl-d cf">
    <div class="search--wrap">
    <div id="search" class="search-bar cf">
      <?php get_search_form(); ?>
    </div>
  </div>
  </div>

<?php if(!is_page('find-plugin-service')) { ;?>
  <div class="wrapper">
<?php } ?>
