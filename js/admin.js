jQuery(document).ready( function() {

	jQuery('#newDom').change(function(){
		var text = this.value;
		var alertBox = '<div id="domain_name_alert" class="error" style="display: inline; padding: 0.6em">' + strings.domainAlert + '</div>';
		if(text.indexOf('www.') != -1) {
			if( jQuery('#domain_name_alert').length == 0 ) {
				jQuery(alertBox).insertAfter(this);
			}
		} else {
			jQuery('#domain_name_alert').remove();
		}
		return false;
	});
});