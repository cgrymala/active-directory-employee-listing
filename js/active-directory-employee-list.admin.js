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
		$( $(this).attr('href') ).toggle();
		return false;
	}
	
	$('a.adel-widget-controls-show-advanced').live( 'click', adel_toggle_hidenote );
	/*$('select.adel-field-selector').each( adel_set_fields_order );
	$('select.adel-field-selector').live( 'change click', adel_set_fields_order );*/
	
	function adel_set_fields_order() {
		var tmp = $(this).find( 'option:selected' );
		if( tmp.length == 0 ) {
			$(this).closest( '.widget-content' ).find( '.adel-field-order' ).val( '' );
			return;
		}
		
		tmp = $(this).find( 'option' );
		
		var fieldsList = {};
		var removeList = {};
		for( var i=0; i<tmp.length; i++ ) {
			if( $(tmp[i]).is(':selected') ) {
				fieldsList[$(tmp[i]).attr('value')] = 1;
			} else {
				removeList[$(tmp[i]).attr('value')] = 1;
			}
		}
		
		var orderList = $(this).closest( '.widget-content' ).find( '.adel-field-order' ).val();
		orderList = adel_array_flip( orderList.split( ' ' ) );
		if( typeof( orderList ) === 'undefined' || orderList.length <= 0 ) {
			orderList = {};
		}
		
		var newList = adel_remove_from_array( orderList, removeList );
		newList = adel_add_to_array( newList, fieldsList );
		
		newList = adel_array_keys( newList );
		
		$(this).closest( '.widget-content' ).find( '.adel-field-order' ).val( newList.join( ' ' ) );
	}
	
	function adel_array_flip( arr ) {
		if( arr.length <= 0 || ( arr.length == 1 && arr[0] == '' ) ) {
			return {};
		}
		
		var newList = {};
		for( var i in arr ) {
			newList[arr[i]] = i;
		}
		return newList;
	}
	
	function adel_array_keys( obj ) {
		var newList = [];
		for( var i in obj ) {
			newList.push( i );
		}
		return newList;
	}
	
	function adel_array_values( obj ) {
		var newList = [];
		for( var i in obj ) {
			newList.push( obj[i] );
		}
		return newList;
	}
	
	function adel_add_to_array( arr1, arr2 ) {
		for( var i in arr2 ) {
			if( !( i in arr1 ) ) {
				arr1[i] = arr2[i];
			}
		}
		return arr1;
	}
	
	function adel_remove_from_array( arr1, arr2 ) {
		var newArr = {};
		for( var i in arr1 ) {
			if( !( i in arr2 ) ) {
				newArr[i] = arr1[i];
			}
		}
		return newArr;
	}
	
	if( typeof( adelData ) !== 'undefined' ) {
		var adelPresets = adelData.presets;
		var adelReqd = adelData.reqdfields;
		
		$('.adel-preset-selector').each( adel_select_presets_init );
		$('.adel-preset-selector').live( 'click change', adel_select_presets );
		/*$('.adel-field-selector').live( 'click change', adel_reset_presets );
		$('.adel-output-builder').live( 'change', adel_reset_presets );*/
		
		function adel_select_presets_init() {
			var $selOpt = $(this).find('option:selected');
			if( $(this).closest('.widget-content').find('.adel-output-builder').val() == '' && $(this).closest('.widget-content').find('.adel-field-selector option:selected').length <= 0 ) {
				adel_select_presets();
			} else {
				var $ob = $(this).closest('.widget-content').find('.adel-output-builder').val().replace( /\s/g, '' );
				var ap = adelPresets[$selOpt.attr('value')].output.replace( /\s/g, '' );
				if( $ob.length != ap.length ) {
					$(this).val('');
					return false;
				} else if( $ob != ap ) {
					$(this).val('');
					return false;
				}
				if( $(this).closest('.widget-content').find('.adel-field-selector option:selected').length != adel_object_length( adelReqd ) ) {
					$(this).val('');
					return false;
				} else {
					$(this).closest('.widget-content').find('.adel-field-selector option:selected').each( function() {
						if( !( $(this).attr('value') in adelReqd ) ) {
							$(this).val('');
							return false;
						}
					} );
				}
			}
		}
		
		function adel_object_length( obj ) {
			var ct = 0;
			for( var i in obj ) {
				ct++;
			}
			return ct;
		}
		
		function adel_select_presets() {
			var $selOpt = $(this).find('option:selected');
			if( $selOpt.length <= 0 || $selOpt.attr('value') == '' ) {
				return;
			}
			var selPreset = $selOpt.attr('value');
			if( selPreset in adelPresets ) {
				$(this).closest('.widget-content').find('.adel-output-builder').val( adelPresets[selPreset].output );
				$(this).closest('.widget-content').find('.adel-field-selector option').each( function() { $(this).removeAttr('selected') } );
				var $fieldSelector = $(this).closest('.widget-content').find('.adel-field-selector');
				for( var i in adelReqd ) {
					$fieldSelector.find('[value="' + i + '"]').attr('selected','selected');
				}
			}
		}
		
		function adel_reset_presets() {
			$(this).closest('.widget-content').find('.adel-preset-selector').val( '' );
		}
	}
	
} );