jQuery( function( $ ) {
	var adel_settings_rows;
	var adel_prefs_rows;
	var adel_output_rows;
	
	$('input[type="checkbox"]#ignore_settings_group').click( adel_check_settings_fields );
	$('input[type="checkbox"]#ignore_prefs_group').click( adel_check_prefs_fields );
	$('input[type="checkbox"]#ignore_output_group').click( adel_check_output_fields );
	$('a.adel-reveal-if-js').live( 'click', adel_toggle_hidenote );
	
	adel_check_settings_fields();
	adel_check_prefs_fields();
	adel_check_output_fields();
	$('.adel-hide-if-js').hide();
	
	function adel_check_settings_fields() {
		var settings_ignore = $('input[type="checkbox"]#ignore_settings_group');
		var si_checked = $('input[type="checkbox"]#ignore_settings_group:checked');
		if( si_checked.length > 0 ) {
			adel_settings_rows = settings_ignore.closest('tr').siblings('tr');
			adel_settings_rows.remove();
		} else {
			if( settings_ignore.closest('tr').siblings('tr').length <= 0 ) {
				settings_ignore.closest('tr').after( adel_settings_rows );
				$('.adel-hide-if-js').hide();
			}
		}
	}
	
	function adel_check_prefs_fields() {
		var settings_ignore = $('input[type="checkbox"]#ignore_prefs_group');
		var si_checked = $('input[type="checkbox"]#ignore_prefs_group:checked');
		if( si_checked.length > 0 ) {
			adel_prefs_rows = settings_ignore.closest('tr').siblings('tr');
			adel_prefs_rows.remove();
		} else {
			if( settings_ignore.closest('tr').siblings('tr').length <= 0 ) {
				settings_ignore.closest('tr').after( adel_prefs_rows );
				$('.adel-hide-if-js').hide();
			}
		}
	}
	
	function adel_check_output_fields() {
		var settings_ignore = $('input[type="checkbox"]#ignore_output_group');
		var si_checked = $('input[type="checkbox"]#ignore_output_group:checked');
		if( si_checked.length > 0 ) {
			adel_output_rows = settings_ignore.closest('tr').siblings('tr');
			adel_output_rows.remove();
		} else {
			if( settings_ignore.closest('tr').siblings('tr').length <= 0 ) {
				settings_ignore.closest('tr').after( adel_output_rows );
				$('.adel-hide-if-js').hide();
			}
		}
	}
	
	function adel_toggle_hidenote() {
		console.log( $(this) );
		$( $(this).attr('href') ).toggle();
		return false;
	}
} );