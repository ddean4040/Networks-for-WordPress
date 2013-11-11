=== Networks for WordPress ===
Contributors: ddean
Tags: multisite, multi-site, sites, networks, multi-network, multiple, multi-domain, domains
Requires at least: 3.0
Tested up to: 3.7.1
Stable tag: 1.1.6

Adds a Networks panel for network admins to create and manage multiple Networks from one WordPress installation.

== Description ==

Adds a Networks panel allowing network admins to create and manage multiple Networks from one WordPress installation.  Each Network can exist on its own domain, and have its own set of blogs / Sites.

Each Network can have its own set of plugins, themes, administrators, permissions, and policies, but all will share a database of user accounts.

Sites can be moved freely among Networks.

= Notes =

Each Network will require changes to your web server and DNS.

See **Frequently Asked Questions** for detailed instructions.

= Translation =

* Slovak translation generously provided by Branco, (<a href="http://webhostinggeeks.com/blog/">WebHostingGeeks.com</a>)


== Installation ==

1. Extract the plugin archive 
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Comment out `DOMAIN_CURRENT_SITE` in your `wp-config.php` file.
1. Copy `networks-mufunctions.php` into your `mu-plugins` folder. This is **not** required but **is** highly recommended. See FAQ for more details.

== Frequently Asked Questions ==

= How do I set up new domains as networks with a single WordPress install? =

Your webserver must direct requests for each domain you want to use to your WordPress install's filesystem location.

Here's a quick overview:

1. DNS should resolve each desired domain to your web server.
1. Configure your web server to direct requests for each desired domain to the same site (e.g. via `ServerAlias` directives or `Host Headers`)

Tip: You can use the New Network Preview feature to verify DNS and server configuration BEFORE creating your networks.

= What's with `networks-mufunctions.php`? =

This file has fixes for unusual network topologies, like networks without a root site or those more than one subdirectory deep.

The fix for networks with deep paths was graciously contributed by Spencer Bryant.

For a long time I avoided bundling fixes like these, since I was committed to having this plugin run only when needed by the dashboard.
This file does not rely on the rest of Networks for Wordpress to function, so you are free to copy it to your mu-plugins folder and use it on its own.

This seemed like a good compromise. If you have thoughts, let me know in the comments!

If you are on WP 3.5 - 3.6.1 and want to use native file uploads, you **must** either:

1. Activate the Networks plugin on all of your networks, OR
2. Copy `networks-muplugins.php` into your `mu-plugins` folder

If you do not do this, your uploaded files will end up in unexpected places.

This is not needed for WP 3.7+.

== Known Issues ==

* Plugins that create global (i.e. not blog-specific) tables will behave as though they are on a single network install.  This is a limitation of WordPress's table naming scheme.

== Changelog ==

= 1.1.6 =
* Changed: Native upload handling on WP 3.7 without this plugin active
* Changed: Made text labels more consistent with WP terminology
* Changed: Made siteurl replacement trailing slash-insensitive when changing network path - fix contributed by mgburns!
* Fixed: changing network domain or path could reset site paths - fix contributed by mgburns!
* Fixed: PHP warning when viewing the Networks admin screen - thanks, zawszaws

= 1.1.5 =
* Added: ability to set upload handling per network
* Added: switch blog_id when switching sites / networks
* Fixed: primary sites on all networks used the same upload path (WP 3.5+ only) - thanks, RavanH

= 1.1.4 =
* Added: Slovak translation provided by Branco, (<a href="http://webhostinggeeks.com/blog/">WebHostingGeeks.com</a>)
* Added: Delete rewrite rules when moving a site - thanks, mgburns
* Changed: New network's root site inherits the search-engine visibility of the site used to create it - thanks, Christian Wach
* Fixed: Squashed PHP warnings when `WP_DEBUG` is true - contributed by Christian Wach
* Fixed: Bug fixes in restore_current_site - contributed by Christian Wach

= 1.1.3 =
* Added: `networks-mufunctions.php` file containing fixes for unusual Network topologies. See FAQ for details
* Changed: made visual changes to fit in with the WP dashboard
* Changed: assign sites select boxes are now bigger (varies by number of sites)
* Fixed: bug that prepended the domain name on to sites whose path matched the network's when moving - thanks, sharonmiranda!
* Fixed: provide SSL network URLs when required - thanks, Spencer Bryant

= 1.1.2 =
* Added: Screen Options for selecting number of Networks per page
* Changed: `add_site` function now defaults to a path of '/' if one is not supplied
* Fixed: searching and sorting by Network Name - thanks, skvwp

= 1.1.1 =
* Added: descriptions for MANY more sitemeta keys, using WP native strings where possible
* Added: placeholders for most text fields
* Added: `is_super_admin_for()` function for checking permissions on other networks
* Changed: reorganized the "Create a Network" panel to be more intuitive
* Changed: (for developers) `$options_to_copy` now carries the sitemeta keys to be cloned as values instead of keys
* Changed: switched to `add_query_arg()` where possible
* Changed: split code up into different files for easier maintenance
* Fixed: hardcoded URL that may have prevented some from deleting networks in bulk

= 1.1.0 =
* Added: New Network Preview will try to check mapped domains for possible collisions
* Changed: `upload_filetypes` is now cloned by default, so you don't have to save settings before uploading files on new networks

= 1.0.9 =
* Added: enhanced help menu for WP 3.3
* Added: ability to restrict Networks Menu to a certain Network with the `RESTRICT_MANAGEMENT_TO` constant
* Added: WP 3.3 help pointer
* Changed: removed ability to delete the primary network
* Fixed: enhanced compatibility with WP < 3.1 - thanks, suresh.sambandam

= 1.0.8 =
* Added: support for the `siteurl` sitemeta entry
* Changed: Updated Help screen to display in WP 3.3
* Fixed: Adding a network now displays the correct update message

= 1.0.7 =
* Added: Network diagnostic screen
* Changed: allow Site assignment page to continue even if a blogname key is missing

= 1.0.6 =
* Added: hooks with new wpms prefixes
* Fixed: enabled checkbox to avoid creating a root blog.  This is for advanced users only.

= 1.0.5 =
* Added: better documentation in the Help tab
* Added: basic automated validation of new Network settings
* Removed: documentation on domain names that no longer applies

= 1.0.4 =
* Changed: show each network only once in Move Site field, regardless of metadata issues
* Fixed: a bug affecting network installs with old table name scheme (created before WP 3.0)
* Fixed: admin display bug affecting WP 3.2 installs

= 1.0.3 =
* Changed: processing to ensure that new Network paths are always valid
* Changed: documentation on new Networks
* Fixed: short_open_tag off compatibility

= 1.0.2 =
* Fixed: a bug that showed the Networks panel in the Site Admin backend on 3.1
* Fixed: a typo that left network-dependent blog options behind when moving blogs - thanks, edmeister

= 1.0.1 =
* Fixed an issue with the link to Network backends for versions before 3.1 - thanks, RavanH

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.1.6 =
* Contributed fixes and better upload handling -- replace `networks-mufunctions.php` if you have copied it to your `mu-plugins` folder

= 1.1.5 =
* Updates to support native upload handling in WP 3.5+ -- see Installation and FAQ for important details!

= 1.1.4 =
* Debug fixes, new translation, and trigger rewrite rules rebuild when moving a site

= 1.1.3 =
* Fixed site moving bug and SSL issue; bundled fixes for unusual Networks (see FAQ)

= 1.1.2 =
* Fixed searching / sorting bug; added screen options for Networks per page

= 1.1.1 =
* UI enhancements

= 1.1.0 =
* `upload_filetypes` key cloned by default, some domain mapping checks added

= 1.0.9 =
* Fixed compatibility with WP < 3.1, enhanced help for WP 3.3, added ability to restrict Networks panel to a single Network

= 1.0.8 =
* Updated for WP 3.3

= 1.0.7 =
* Added diagnostic screen to help identify and resolve Network issues

= 1.0.6 =
* Enabled checkbox to avoid creating a new root blog - for advanced users only

= 1.0.5 =
* Documentation and interface improvements only

= 1.0.3 =
* Documentation and processing changes

= 1.0.2 =
* Bugfix - All users should upgrade

= 1.0.1 =
* Upgrade if using a version of WordPress earlier than 3.1

= 1.0 =
* Initial release - upgrade if you are still using my old WPMU Multi-Site Manager plugin somehow
