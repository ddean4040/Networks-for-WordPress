<?php

if(!function_exists('site_exists')) {

	/**
	 * Check to see if a site exists. Will check the sites object before checking the database.
	 * @param integer $site_id ID of site to verify
	 * @return boolean TRUE if found, FALSE otherwise
	 */
	function site_exists($site_id) {
		global $sites, $wpdb;
		$site_id = (int)$site_id;
		if(isset($sites)) {
			foreach($sites as $site) {
				if($site_id == $site->id) {
					return TRUE;
				}
			}
		}
		
		/** check db just to be sure or if $sites, above, was not yet defined */
		$site_list = $wpdb->get_results('SELECT id FROM ' . $wpdb->site);
		if($site_list) {
			foreach($site_list as $site) {
				if($site->id == $site_id) {
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
}

if(!function_exists('switch_to_site')) {

	/**
	 * Problem: the various *_site_options() functions operate only on the current site
	 * Workaround: change the current site
	 * @param integer $new_site ID of site to manipulate
	 */
	function switch_to_site($new_site) {
		global $tmpoldsitedetails, $wpdb, $site_id, $switched_site, $switched_site_stack, $current_site, $sites;

		if ( !site_exists($new_site) )
			$new_site = $site_id;

		if ( empty($switched_site_stack) )
			$switched_site_stack = array();

		$switched_site_stack[] = $site_id;

		if ( $new_site == $site_id )
			return;

		// backup
		$tmpoldsitedetails[ 'site_id' ] 	= $site_id;
		$tmpoldsitedetails[ 'blog_id' ]     = $current_site->blog_id;
		$tmpoldsitedetails[ 'id']			= $current_site->id;
		$tmpoldsitedetails[ 'domain' ]		= $current_site->domain;
		$tmpoldsitedetails[ 'path' ]		= $current_site->path;
		$tmpoldsitedetails[ 'site_name' ]	= $current_site->site_name;

		// All site info is pre-fetched into the $sites global -- just pull out the one we want
		foreach($sites as $site) {
			if($site->id == $new_site) {
				$current_site = $site;
				break;
			}
		}

		$wpdb->siteid			 = $new_site;
		$current_site->blog_id   = $wpdb->get_var( 
			$wpdb->prepare( 
				'SELECT blog_id FROM ' . $wpdb->blogs . ' WHERE site_id=%d AND domain=%s AND path=%s',
				$new_site,
				$current_site->domain,
				$current_site->path
			)
		);
		$current_site->site_name = get_site_option('site_name');
		$site_id = $new_site;

		do_action( 'switch_site', $site_id, $tmpoldsitedetails[ 'site_id' ] );
		do_action( 'switch_network', $site_id, $tmpoldsitedetails[ 'site_id' ] );

		$switched_site = true;
	}
}

if(!function_exists('restore_current_site')) {

	/**
	 * Return to the operational site after our operations
	 */
	function restore_current_site() {
		global $tmpoldsitedetails, $wpdb, $site_id, $switched_site, $switched_site_stack, $current_site;

		if ( !$switched_site )
			return;

		$site_id = array_pop($switched_site_stack);

		if ( $site_id == $current_site->id )
			return;

		// backup

		$prev_site_id = $wpdb->siteid;

		$wpdb->siteid = $site_id;
		$current_site->id = $tmpoldsitedetails[ 'id' ];
		$current_site->domain = $tmpoldsitedetails[ 'domain' ];
		$current_site->path = $tmpoldsitedetails[ 'path' ];
		$current_site->site_name = $tmpoldsitedetails[ 'site_name' ];
		$current_site->blog_id = $tmpoldsitedetails[ 'blog_id' ];

		unset( $tmpoldsitedetails );

		do_action( 'switch_site', $site_id, $prev_site_id );
		do_action( 'switch_network', $site_id, $prev_site_id );

		$switched_site = false;
		
	}
}

if(!function_exists('wpmu_create_site')) {
	function wpmu_create_site($domain, $path, $blog_name = NULL, $cloneSite = NULL, $options_to_clone = NULL) {
		return add_site( $domain, $path, $blog_name = NULL, $cloneSite = NULL, $options_to_clone = NULL );
	}
}

if (!function_exists('add_site')) {

	/**
	 * Create a new network
	 * 
	 * @uses site_exists()
	 * @uses wpmu_create_blog()
	 * @uses switch_to_site()
	 * @uses restore_current_site()
	 * 
	 * @param string $domain domain name for new network - for subdirectory installs, this should be a FQDN, otherwise domain only
	 * @param string $path path to root of network hierarchy - should be '/' unless WordPress is sharing a domain with normal web pages
	 * @param string $blog_name Name of the root blog to be created on the new network or FALSE to skip creating a root blog
	 * @param integer $cloneSite ID of network whose sitemeta values are to be copied - default NULL
	 * @param array $options_to_clone override default sitemeta options to copy when cloning - default NULL
	 * @return integer ID of newly created network
	 */
	function add_site($domain, $path = '/', $blog_name = NULL, $cloneSite = NULL, $options_to_clone = NULL) {

		global $wpdb, $sites, $options_to_copy, $url_dependent_site_options, $current_site;

		$skip_blog_setup = ($blog_name === false);
		if($blog_name == NULL) $blog_name = __('New Network Created','njsl-networks');

		$options_to_clone = wp_parse_args( $options_to_clone, array_keys($options_to_copy) );
		
		$domain = untrailingslashit( $domain );
		
		if($path != '/') {
			$path = trim( $path, '/' );
			$path = trailingslashit( '/' . $path );
		}

		$query = "SELECT * FROM {$wpdb->site} WHERE domain='" . $wpdb->escape($domain) . "' AND path='" . $wpdb->escape($path) . "' LIMIT 1";
		$site = $wpdb->get_row($query);
		if($site) {
			return new WP_Error('site_exists',__('Network already exists!','njsl-networks'));
		}
		
		$wpdb->insert($wpdb->site,array(
			'domain'	=> $domain,
			'path'		=> $path
		));
		$new_site_id =  $wpdb->insert_id;
			
		/* update site list */
		$sites = $wpdb->get_results('SELECT * FROM ' . $wpdb->site);

		if($new_site_id) {
			
			add_site_option( 'siteurl', $domain . $path );
			
			/* prevent ugly database errors - #184 */
			if(!defined('WP_INSTALLING')) {
				define('WP_INSTALLING',TRUE);
			}
			
			if(!$skip_blog_setup) {
				// there's an ongoing error with wpmu_create_blog that throws a warning if meta is not defined:
				// http://core.trac.wordpress.org/ticket/20793
				// temporary fix -- set from current blog's value
				$new_blog_visibility = get_option( 'blog_public', false );
				
				$new_blog_id = wpmu_create_blog(
					$domain,
					$path,
					$blog_name,
					get_current_user_id(),
					array( 'public' => $new_blog_visibility ),
					(int)$new_site_id
				);
				
				if(is_a($new_blog_id,'WP_Error')) {
					return $new_blog_id;
				}
				
				// Fix upload_path for main sites on secondary networks
				// This applies only to new installs (WP 3.5+)

				// Switch to main network (if it exists)
				if( site_exists( 1 ) ) {
					switch_to_site( 1 );
					$use_files_rewriting = get_site_option( 'ms_files_rewriting' );
					restore_current_site();
				} else {
					$use_files_rewriting = get_site_option( 'ms_files_rewriting' );
				}
				
				// Create the upload_path and upload_url_path values for WP 3.5 - 3.6.1
				
				global $wp_version;
				
				if( ! $use_files_rewriting && version_compare( $wp_version, '3.7', '<' ) ) {

					// WP_CONTENT_URL is locked to the current site and can't be overridden,
					//  so we have to replace the hostname the hard way
					$current_siteurl = get_option('siteurl');
					$new_siteurl = untrailingslashit( get_blogaddress_by_id( $new_blog_id ) );
					$upload_url = str_replace( $current_siteurl, $new_siteurl, WP_CONTENT_URL );
					$upload_url = $upload_url . '/uploads';
					
					$upload_dir = WP_CONTENT_DIR;
					if( 0 === strpos( $upload_dir, ABSPATH ) ) {
						$upload_dir = substr( $upload_dir, strlen( ABSPATH ) );
					}
					$upload_dir .= '/uploads';
					
					if ( defined( 'MULTISITE' ) )
						$ms_dir = '/sites/' . $new_blog_id;
					else
						$ms_dir = '/' . $new_blog_id;
					
					$upload_dir .= $ms_dir;
					$upload_url .= $ms_dir;
					
					update_blog_option( $new_blog_id, 'upload_path', $upload_dir );
					update_blog_option( $new_blog_id, 'upload_url_path', $upload_url );
					
				}
				
			}
		}
		
		/** if selected, copy the sitemeta from an existing site */
				
		if(!is_null($cloneSite) && site_exists($cloneSite)) {

			$optionsCache = array();
			
			switch_to_site((int)$cloneSite);
			
			foreach($options_to_clone as $option) {
				$optionsCache[$option] = get_site_option($option);
			}
			
			$oldsite_domain = $current_site->domain;
			$oldsite_path   = $current_site->path;
			
			restore_current_site();

			switch_to_site($new_site_id);
			
			foreach($options_to_clone as $option) {
				if($optionsCache[$option] !== false) {
					if(in_array($option, $url_dependent_site_options)) {
						$optionsCache[$option] = str_replace($oldsite_domain . $oldsite_path, $domain . $path, $optionsCache[$option]);
					}
					
					// Fix for strange bug that prevents writing the ms_files_rewriting value for new networks
					if( $option == 'ms_files_rewriting' ) {
						$wpdb->insert( $wpdb->sitemeta, array('site_id' => $wpdb->siteid, 'meta_key' => $option, 'meta_value' => $optionsCache[$option] ) );
					} else {
						add_site_option($option, $optionsCache[$option]);
					}
				}
			}
			unset($optionsCache);
			
			restore_current_site();

		}

		do_action( 'wpmu_add_site' , $new_site_id );
		do_action( 'wpms_add_network' , $new_site_id );

		return $new_site_id;
	}
}

if (!function_exists('update_site')) {

	/**
	 * Modify the domain and path of an existing site - and update all of its blogs
	 * @param integer id ID of site to modify
	 * @param string $domain new domain for site
	 * @param string $path new path for site
	 * @param array $meta meta keys and values to be updated
	 */
	function update_site($id, $domain, $path='', $meta = null ) {

		global $wpdb, $url_dependent_blog_options, $wp_version;

		if(!site_exists((int)$id)) {
			return new WP_Error('site_not_exist',__('Network does not exist.','njsl-networks'));
		}

		$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$id;
		$site = $wpdb->get_row($query);
		if(!$site) {
			return new WP_Error('site_not_exist',__('Network does not exist.','njsl-networks'));
		}

		$domain = untrailingslashit($domain);
		$update = array('domain'	=> $domain);

		if($path != '') {
			$path = trim( $path, '/' );
			$path = trailingslashit( '/' . $path );
			$update['path'] = $path;
		}

		$where = array('id'	=> (int)$id);
		$update_result = $wpdb->update($wpdb->site,$update,$where);

		if( false === $update_result ) {
			return new WP_Error('site_not_updatable',__('Network could not be updated.','njsl-networks'));
		}

		if( ! empty( $meta ) && is_array( $meta ) ) {
			
			switch_to_site( $id );
			
			foreach( $meta as $option => $value ) {
				
				if( false === update_site_option( $option, $value ) ) {
					$wpdb->insert( $wpdb->sitemeta, array('site_id' => $wpdb->siteid, 'meta_key' => $option, 'meta_value' => $value ) );
				}
				
			}
			
			restore_current_site();
				
		}
		
		// No URL values updated -- quit before needlessly 
		//   processing a bunch of URL-specific values and triggering hooks
		if( ! $update_result )
			return;
		
		$path = (($path != '') ? $path : $site->path );
		$fullPath = untrailingslashit( $domain . $path );
		$oldPath = untrailingslashit( $site->domain . $site->path );

		/** also updated any associated blogs */
		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id=" . (int)$id;
		$blogs = $wpdb->get_results($query);
		if($blogs) {
			foreach($blogs as $blog) {
				$update = array();

				if($site->domain !== $domain) {
					$update['domain'] = str_replace($site->domain,$domain,$blog->domain);
				}

				if($site->path !== $path) {
					$search = sprintf('|^%s|', preg_quote($site->path, '|'));
					$update['path'] = preg_replace($search, $path, $blog->path, 1);
				}

				if(empty($update))
					continue;

				$blog_id = (int) $blog->blog_id;
				switch_to_blog($blog_id);

				$wpdb->update(
					$wpdb->blogs,
					$update,
					array(	'blog_id'	=> $blog_id	)
				);

				foreach($url_dependent_blog_options as $option_name) {
					// TODO: pop upload_url_path off list if ms_files_rewriting is disabled
					$option_value = get_option( $option_name );
					if($option_value) {
						$newValue = str_replace($oldPath,$fullPath,$option_value->option_value);
						update_option($option_name,$newValue);
					}
				}
				restore_current_blog();

				refresh_blog_details($blog_id);
			}
		}
		
		do_action( 'wpmu_update_site' , $id, array('domain'=>$site->domain, 'path'=>$site->path) );
		do_action( 'wpms_update_network' , $id, array('domain'=>$site->domain, 'path'=>$site->path) );
		
	}
}

if (!function_exists('delete_site')) {

	/**
	 * Delete a site and all its blogs
	 * 
	 * @uses move_blog()
	 * @uses wpmu_delete_blog()
	 * 
	 * @param integer id ID of site to delete
	 * @param boolean $delete_blogs flag to permit blog deletion - default setting of FALSE will prevent deletion of occupied sites
	 */
	function delete_site($id,$delete_blogs = FALSE) {
		global $wpdb;

		$override = $delete_blogs;

		/* ensure we got a valid site id */
		$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$id;
		$site = $wpdb->get_row($query);
		if(!$site) {
			return new WP_Error('site_not_exist',__('Network does not exist.','njsl-networks'));
		}

		/* ensure there are no blogs attached to this site */
		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id=" . (int)$id;
		$blogs = $wpdb->get_results($query);
		if($blogs && !$override) {
			return new WP_Error('site_not_empty',__('Cannot delete network with sites.','njsl-networks'));
		}

		if($override) {
			if($blogs) {
				foreach($blogs as $blog) {
					if(RESCUE_ORPHANED_BLOGS && ENABLE_HOLDING_SITE) {
						move_blog( $blog->blog_id, 0 );
					} else {
						wpmu_delete_blog( $blog->blog_id, true );
					}
				}
			}
		}

		$query = "DELETE FROM {$wpdb->site} WHERE id=" . (int)$id;
		$wpdb->query($query);

		$query = "DELETE FROM {$wpdb->sitemeta} WHERE site_id=" . (int)$id;
		$wpdb->query($query);
		
		do_action( 'wpmu_delete_site' , $site );
		do_action( 'wpms_delete_network' , $site );
	}
}

if(!function_exists('move_blog')) {

	/**
	 * Move a blog from one site to another
	 * @param integer $blog_id ID of blog to move
	 * @param integer $new_site_id ID of destination site
	 */
	function move_blog($blog_id, $new_site_id) {

		global $wpdb;
		global $url_dependent_blog_options;

		/* sanity checks */
		$query = "SELECT * FROM {$wpdb->blogs} WHERE blog_id=" . (int)$blog_id;
		$blog = $wpdb->get_row($query);
		if(!$blog) {
			return new WP_Error('blog not exist',__('Site does not exist.','njsl-networks'));
		}

		/** If user requested moving site to its current network, just return that we did */
		if((int)$new_site_id == $blog->site_id) { return true;	}
		
		$old_site_id = $blog->site_id;
		
		if(ENABLE_HOLDING_SITE && $blog->site_id == 0) {
			$oldSite->domain = 'holding.blogs.local';
			$oldSite->path = '/';
			$oldSite->id = 0;
		} else {
			$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$blog->site_id;
			$oldSite = $wpdb->get_row($query);
			if(!$oldSite) {
				return new WP_Error('site_not_exist',__('Network does not exist.','njsl-networks'));
			}
		}

		if($new_site_id == 0 && ENABLE_HOLDING_SITE) {
			$newSite->domain = 'holding.blogs.local';
			$newSite->path = '/';
			$newSite->id = 0;
		} else {
			$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$new_site_id;
			$newSite = $wpdb->get_row($query);
			if(!$newSite) {
				return new WP_Error('site_not_exist',__('Network does not exist.','njsl-networks'));
			}
		}

		if( is_subdomain_install() ) {

			if( $blog->domain == $oldSite->domain ) {

				/** If this site has no hostname component, just replace the domain name outright */
				$domain = $newSite->domain;
				
			} else {

				$exDom = substr( $blog->domain, 0, ( strpos( $blog->domain, '.' ) + 1 ) );
				$domain = $exDom . $newSite->domain;
				
			}
			
		} else {

			$domain = $newSite->domain;
			
		}
		$path = $newSite->path . substr($blog->path,strlen($oldSite->path) );
		
		$update_result = $wpdb->update(
			$wpdb->blogs,
			array(	'site_id'	=> $newSite->id,
					'domain'	=> $domain,
					'path'		=> $path
			),
			array(	'blog_id'	=> $blog->blog_id)
		);
			
		if( ! $update_result ) {
			return new WP_Error( 'blog_not_moved', __( 'Site could not be moved.', 'njsl-networks' ) );
		}
		
		/** change relevant blog options */
		$optionTable = $wpdb->get_blog_prefix( $blog->blog_id ) . 'options';

		$oldDomain = $oldSite->domain . $oldSite->path;
		$newDomain = $newSite->domain . $newSite->path;

		foreach( $url_dependent_blog_options as $option_name ) {
			$option = $wpdb->get_row("SELECT * FROM $optionTable WHERE option_name='" . $option_name . "'");
			$newValue = str_replace($oldDomain,$newDomain,$option->option_value);
			update_blog_option($blog->blog_id,$option_name,$newValue);
		}
		
		// Delete rewrite rules for site at old URL
		delete_blog_option( $blog->blog_id, 'rewrite_rules' );
		
		do_action( 'wpmu_move_blog' , $blog_id, $old_site_id, $new_site_id );
		do_action( 'wpms_move_site' , $blog_id, $old_site_id, $new_site_id );
	}
}

if( ! function_exists( 'is_super_admin_for' ) ) {
	
	/**
	 * Returns whether current user is a site / super admin for the selected site(s)
	 * @param int|array Site ID(s) site ID or array of site IDs to check user status
	 * @return bool TRUE if user is super admin for all selected sites, FALSE otherwise
	 */
	function is_super_admin_for( $site_ids = null ) {
		
		if( empty( $site_ids ) )	return is_super_admin();
		
		global $wpdb;
		$user = wp_get_current_user();
		
		if( is_int( $site_ids ) )
			$site_ids = array( $site_ids );
		
		$is_super_admin = true;
		
		foreach( $site_ids as $site_id ) {
			
			if( ! $super_admins = wp_cache_get( $site_id, 'super_admins_for' ) ) {
				
				$super_admins = maybe_unserialize($wpdb->get_var( 
					$wpdb->prepare(
						'SELECT meta_value as site_admins FROM ' . $wpdb->sitemeta . ' WHERE meta_key=%s AND site_id=%d',
						'site_admins',
						$site_id
					)
				));
				
				wp_cache_set( $site_id, $super_admins, 'super_admins_for' );
				
			}
			
			$is_super_admin = $is_super_admin && in_array( $user->user_login, $super_admins);
			
			if( ! $is_super_admin )	break; // no need to keep looking once one fails
		}
		
		return $is_super_admin;
	}
	
}

add_action( 'granted_super_admin', 'wp_networks_clear_super_admin_cache' );
add_action( 'revoked_super_admin', 'wp_networks_clear_super_admin_cache' );

/**
 * Invalidate cache when someone is promoted / demoted
 */
function wp_networks_clear_super_admin_cache( $user_id ) {
	
	if( $current_site = get_current_site() ) {
		wp_cache_delete( $current_site->ID, 'super_admins_for' );
	}
	
}


?>
