=== Networks for WordPress ===
Contributors: ddean
Tags: multisite, sites, networks, multi-networks, domains
Requires at least: WPMU 2.7
Tested up to: 3.1
Stable tag: 1.0.1

Adds a Networks panel for network admins to create and manage multiple networks from one WordPress installation.

== Description ==

Adds a Networks panel allowing network admins to create and manage multiple Networks from one WordPress installation.  Each Network can exist on its own domain, and have its own set of blogs / sites.

Each Network can have its own set of plugins, themes, administrators, permissions, and policies, but all will share a database of user accounts.

Sites can be moved freely among Networks.

= Notes =

Each Network will require changes to your webserver and manual file changes.
You can choose: changing WordPress core files once or creating new files for each Network.

We are exploring ways to automate this process, but for now it can be tricky.  See **Frequently Asked Questions** for detailed instructions.

== Installation ==

1. Extract the plugin archive 
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How do I set up new domains to work with a single WordPress install? =

Your webserver must direct requests for each domain you want to use to your WordPress files. There are two main ways to accomplish this:

**1. Separate directories / separate index.php files**

1. DNS should resolve each desired domain to your webserver.
1. Before you begin, move the `DOMAIN_CURRENT_SITE`, `PATH_CURRENT_SITE`, and `SITE_ID_CURRENT_SITE` directives from your `wp-config.php` file to your `index.php` file.
1. Copy your WordPress `index.php` and `.htaccess` files into the directory for the new domain.
1. Update the copy of `index.php` in the new directory to point to the original `wp-blog-header.php` file.
1. Symlink or alias the `wp-includes`, `wp-content`, and `wp-admin` directories from your WordPress install into the new directory.

**2. One directory / WordPress core modifications**

1. DNS should resolve each desired domain to your webserver.
1. Change `/wp-includes/ms-load.php` or `/wpmu-settings.php` file (depending on your WordPress version) to handle multiple rows from the `sites` table.
1. Your webserver should serve the WordPress files when someone requests the desired domain ( via a new VirtualHost with appropriate DocumentRoot, or with a ServerAlias directive )
1. If you create a new VirtualHost and use per-VirtualHost Directory statements, ensure that the new VirtualHost’s Directory statement has adequate permissions.

== Known Issues ==

* Plugins that create global (i.e. not blog-specific) tables will behave as though they are on a single network install.  This is a limitation of WordPress's table naming scheme.

== Changelog ==

= 1.0.1 =
* Fixed an issue with the link to Network backends for versions before 3.1 - thanks, RavanH

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.0.1 =
* Upgrade if using a version of WordPress earlier than 3.1

= 1.0 =
* Initial release - upgrade if you are still using my old WPMU Multi-Site Manager plugin somehow
