<?php
/**
 * This file has fixes and tweaks to make life easier for admins running multiple Network installs.
 * Most of these apply only to specific situations, but they are safe for any install. These do not
 * need the rest of Networks for Wordpress to be installed or activated. Feel free to copy this file 
 * into your muplugins directory to ensure continuity during plugin upgrades.
 */

add_action( 'admin_init', 'override_base_var');

add_filter( 'network_site_url', 'fix_network_site_url', 10, 3);
add_filter( 'redirect_network_admin_request', 'fix_network_admin_redirect' );
add_filter( 'blog_option_upload_path', 'fix_subsite_upload_path', 10, 2 );


/**
 * Update $base global variable to match site path
 * @author Spencer Bryant (szb0018@auburn.edu)
 */
if( ! function_exists( 'override_base_var' ) ) {
	function override_base_var() {
		
		global $base;
		
		if ( ! is_multisite() ) return;
		
		$base = get_current_site()->path;
		
	}
}

/**
 * Rewrite Network Admin URL when there is not a root site
 */
if( ! function_exists( 'fix_network_site_url' ) ) {
	function fix_network_site_url( $url, $path, $scheme ) {
		global $current_site, $current_blog, $wpdb;
		
		if( $current_blog->path != $current_site->path ) {
			if( ! $current_site->blog_id ) {
				$current_site->blog_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM " . $wpdb->blogs . " WHERE domain = %s AND archived = '0' AND spam = 0 AND deleted = 0 ORDER BY registered LIMIT 1", $current_site->domain ) );
			}
			if( ! $current_site->blog_id ) {
				wp_die( __( 'Unable to locate a valid site on this network.', 'njsl-networks' ), __( 'No Valid Site Found', 'njsl-networks' ) );
			}
			$blog = get_blog_details( $current_site->blog_id, false );
			$url = str_replace( $current_site->domain . $current_site->path, $current_site->domain . $blog->path, $url );
		}
		return $url;
	}
}

/**
 * Rewrite Network Admin redirect when there is not a root site
 */
if( ! function_exists( 'fix_network_admin_redirect' ) ) {
	function fix_network_admin_redirect( $do_redirect ) {
		global $current_site, $current_blog, $wpdb;
		if( $do_redirect ) {
			if( ! $current_site->blog_id ) {
				$current_site->blog_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM " . $wpdb->blogs . " WHERE domain = %s AND archived = '0' AND spam = 0 AND deleted = 0 ORDER BY registered LIMIT 1", $current_site->domain ) );
			}
			if( ! $current_site->blog_id ) {
				wp_die( __( 'Unable to locate a valid site on this network.', 'njsl-networks' ), __( 'No Valid Site Found', 'njsl-networks' ) );
			}
			$blog = get_blog_details( $current_site->blog_id, false );
			return ( $current_site->domain != $current_blog->domain ) || ( $current_blog->path != $blog->path );
		}
		return $do_redirect;
	}
}

/**
 * Blank out the value of upload_path when creating a new subsite
 */
if( ! function_exists( 'fix_subsite_upload_path' ) ) {
	
	function fix_subsite_upload_path( $value, $blog_id ) {
		global $current_site, $wp_version;
		
		if( version_compare( $wp_version, '3.7', '<' ) )
			return $value;
		
		if ( $blog_id == $current_site->blog_id ) {
			
			if( ! get_option( 'WPLANG' ) ) {
				return '';
			}
		}
		return $value;
	}
	
}

?>
