# Tidy Repo

<a href="https://assembly.com/tidy-repo/bounties?utm_campaign=assemblage&utm_source=tidy-repo&utm_medium=repo_badge"><img src="https://asm-badger.herokuapp.com/tidy-repo/badges/tasks.svg" height="24px" alt="Open Tasks" /></a>

## A curated list of the best WordPress plugins

Welcome to Tidy Repo, a curated list of WordPress plugins.

I've made it as simple as possible to get started developing this site locally, staying more or less up to date with the data on the live site.

To get started,

1. Clone this repo into an empty (deleted) wp-content folder of a fresh WordPress install
2. Go into the WordPress admin and activate all plugins
3. Go to Tools -> Import and select "Options"
4. Upload the JSON file included in this repo (e.g. tidyrepo.wp_options.2015-04-21.json)
5. Select the "All Options" option, and check the box labeled "Override existing options" and click Import
6. Go to Tools -> Import and select "WordPress"
7. Upload the included XML file in this repo (e.g. tidyrepo.wordpress.2015-04-21.xml), making sure to check the "Download and import file attachments" checkbox. This will take a little while.
8. It's okay to attribute all posts in the import to whatever admin you have set up on your local site

This should be enough to get you started with a fully working site that mirrors the live site

### Notes on Plugin
The only plugin not included in this repo is Gravity Forms, which is a premium plugin. We would definitely love a free alternatives to this at some point.

### Notes on Theme
The Theme makes use of SASS to make managing CSS a bit easier. There are improvements to be made here, but I've used Codekit in the past to compile this SASS. This can be compiled with any tool though, as long as it compiles to "style.css" in the root directory of the "tidy-repo" theme.

The theme also uses SVGs for icons. These are compiled in the "svgs" folder using Grunt.
