<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content after
 *
 * @package tidy repo
 */
?>

    <div class="footer-push"></div>
<?php if(!is_page('find-plugin-service')) { ;?>
</div>
<?php } ?>
<footer class="footer">
    <div class="ft--block wrapper">

    <div class="g">
        <div id="ft-about" class="u-1-3 papa-full ft-box">
            <h3>About Tidy Repo</h3>

            <p>Tidy Repo is a curated list of the best and most functional WordPress plugins from the repository and around the web. We put each plugin listed here through vigorous testing to ensure that it won't break your site and it won't muck up your code. If it's not dependable, it's not listed - it's really that simple. We add a few every week, so be sure to check back or subscribe.</p>
        </div>

        <div id="ft-cat" class="u-2-3 papa-full">
            <div class="ft-box ft-right">
                <h3>Plugin Categories</h3>

                <?php global $categories ?>

                <ul class="list lst-triple">
                    <?php
                        foreach($categories as $category) { 
                            $catID = $category->cat_ID; ?>

                            <li class="list__item">
                                <a href="<?php echo get_category_link( $category->term_id ); ?>"><?php echo $category->name; ?></a>
                            </li>
                        <?php }
                    ?> 
                </ul>
            </div>
       

            <div id="ft-nav-copy" class="ft-box">
                <nav class="ft-nav">
                    <a href="https://twitter.com/tidyrepo" target="_blank">
                        <svg viewBox="0 0 100 100" class="icon shape-twitter">
                            <use xlink:href="#shape-twitter"></use>
                        </svg>

                        <span class="visuallyhidden">Twitter</span>
                    </a>

                    <a href="https://www.facebook.com/tidyrepo" target="_blank">
                        <svg viewBox="0 0 100 100" class="icon shape-facebook">
                            <use xlink:href="#shape-facebook"></use>
                            <span class="visuallyhidden">Facebook</span>
                        </svg>
                    </a>

                    <a href="http://feed.tidyrepo.com/" target="_blank">
                        <svg viewBox="0 0 100 100" class="icon shape-rss">
                            <use xlink:href="#shape-rss"></use>
                        </svg>

                        <span class="visuallyhidden">RSS</span>
                    </a>

                    <a class="nav--block push-top" href="/terms">Terms &amp; Conditions</a>
                    <a class="nav--block" href="/privacy">Privacy &amp; Cookies Policy</a>
                </nav>

                <p>Copyright &copy; <?php echo date("Y"); ?>. Tidy Repo. <br />All Rights Reserved.</p>
        </div>
    </div>
</div>

</footer>

<?php wp_footer(); ?>

<script>
    <?php include_once("js/scripts-min.js"); ?>
</script>

</body>
</html>