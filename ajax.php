<?php

/**
 * Attempt to verify this domain for network creation
 */
function networks_check_domain() {
	
	global $wpdb;
	
	$domain = $_POST['domain'];
	$path = $_POST['path'];
	
	/** DNS */
	$domain_addr = gethostbyname($domain);
	$current_addr = gethostbyname($_SERVER['HTTP_HOST']);
	if($domain_addr == $current_addr) {
		$dns_result = 'IP address for the new domain is a match!';
		$dns_result_class = 'success';
	} else {
		$dns_result = 'New domain IP (' . $domain_addr . ') does not match current IP (' . $current_addr . ').<br />Check your DNS settings.';
		$dns_result_class = 'error';
	}
	
	/** Domain availability */
		$site = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->site} WHERE domain='%s'", 
			$wpdb->escape($domain)
		));
		if($site == 0) {
			$site_result = 'This domain is available!';
			$site_result_class = 'success';
			
			$path_result = 'This path is available!';
			$path_result_class = 'success';
			
		} else {

			$site_result = 'One or more networks exist on this domain.';
			$site_result_class = 'error';

			/** Path availability */
			$path = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->site} WHERE domain='%s' AND path='%s'", 
				$wpdb->escape($domain),
				$wpdb->escape($path)
			));
			
			if($path == 0) {
				$path_result = 'This path is available, but there are other networks on this domain.';
				$path_result_class = 'warning';
			} else {
				$path_result = 'This path is NOT available.';
				$path_result_class = 'error';
			}
			
		}
	
	?>
	<div id="network_verify_result">
	<h5>Results:</h5>
	<ul>
		<li class="<?php echo $dns_result_class ?>">DNS: <?php echo $dns_result; ?></li>
		<li class="<?php echo $site_result_class ?>">Domain: <?php echo $site_result; ?></li>
		<li class="<?php echo $path_result_class ?>">Path: <?php echo $path_result; ?></li>
	</ul>
	</div>
	<?php
	die();
}


?>