<?php
/**
 * Administration functions and class definition for the Active Directory Employee List plugin
 * @package Active-Directory-Employee-List
 * @subpackage Administration
 * @version 0.3
 */

if( !class_exists( 'active_directory_employee_list' ) )
	/**
	 * Require the parent class if it doesn't already exist
	 */
	require_once( 'class-active-directory-employee-list.php' );

if( !class_exists( 'active_directory_employee_list_admin' ) ) {
	/**
	 * Class definition for administration items in the Active Directory Employee List
	 */
	class active_directory_employee_list_admin extends active_directory_employee_list {
		/**
		 * An array to hold the current settings for this plugin
		 * If the settings don't exist or are empty for the area (site, network, multinetwork) in which 
		 * 		the settings are being edited, this array will be empty.
		 * This array is only used to populate the values on the settings edit screen. It does not hold the 
		 * 		actual configuration values (which are stored in their own individual properties of this object).
		 * @var array
		 */
		protected $_settings 			= array();
		/**
		 * An array to hold the current preferences for this plugin
		 * If the preferences don't exist or are empty for the area (site, network, multinetwork) in which 
		 * 		the settings are being edited, this array will be empty.
		 * This array is only used to populate the values on the preferences edit screen. It does not hold the 
		 * 		actual configuration values (which are stored in their own individual properties of this object).
		 * @var array
		 */
		protected $_prefs 				= array();
		/**
		 * An array to hold the current output settings for this plugin
		 * If the settings don't exist or are empty for the area (site, network, multinetwork) in which 
		 * 		the settings are being edited, this array will be empty.
		 * This array is only used to populate the values on the preferences edit screen. It does not hold the 
		 * 		actual configuration values (which are stored in their own individual properties of this object).
		 * @var array
		 */
		protected $_output_opts			= array();
		
		/**
		 * Whether or not the settings options have already been run through the sanitization filter
		 * @var bool
		 * @default false
		 */
		protected $_sanitized			= false;
		
		/**
		 * Whether or not this plugin is being used in a multinetwork environment
		 * Depends on the is_multinetwork functions (not currently publicly available)
		 * @private
		 * @var bool
		 * @default false
		 */
		protected $is_multinetwork		= false;
		/**
		 * Whether or not the user is currently on the primary network (site ID 1)
		 * Depends on the is_multinetwork functions (not currently publicly available)
		 * @private
		 * @var bool
		 * @default false
		 */
		protected $_is_primary_network	= false;
		/**
		 * Whether the user is currently editing the options for the whole multinetwork setup
		 * Depends on the is_multinetwork functions (not currently publicly available)
		 * @private
		 * @var bool
		 * @default false
		 */
		protected $_is_mn_settings_page	= false;
		
		/**
		 * A static array to hold the titles for the sections of options
		 * @var array
		 */
		var $settings_titles		= array( 'ad_employee_list_settings' => 'Active Directory Settings', 'ad_employee_list_prefs' => 'List Preferences', 'ad_employee_list_output_opts' => 'List Output Settings' );
		
		/**
		 * Build the object
		 * @uses active_directory_employee_list::__construct()
		 */
		function __construct() {
			parent::__construct();
			$this->_is_multinetwork();
			
			if( is_network_admin() && $this->is_multinetwork && 1 == $GLOBALS['current_site']->id ) {
				$this->_is_primary_network = true;
				if( 'sites.php?page=' . $this->settings_page === basename( $_SERVER['REQUEST_URI'] ) ) {
					$this->_is_mn_settings_page = true;
				} else {
					$page = $this->settings_page;
					foreach( array( $this->settings_name, $this->prefs_name, $this->output_name ) as $id ) {
						$id = 'meta-' . $id;
						add_filter( "postbox_classes_{$page}_{$id}", array( &$this, 'close_metabox' ), 1, 1 );
					}
				}
			}
			
			add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( &$this, '_init_admin' ) );
			
			add_action( 'add_site_option', 		array( $this, 'maybe_delete_site_option' 	), 99, 2 );
			add_action( 'add_option', 			array( $this, 'maybe_delete_option' 		), 99, 2 );
			add_action( 'update_site_option', 	array( $this, 'maybe_delete_site_option' 	), 99, 2 );
			add_action( 'update_option', 		array( $this, 'maybe_delete_option' 		), 99, 2 );
			
			if( !function_exists( 'ldap_connect' ) ) {
				add_action( 'admin_notices', 			array( $this, '_ldap_not_supported' ) );
				add_action( 'network_admin_notices', 	array( $this, '_ldap_not_supported' ) );
				return;
			}
			
			if( isset( $_REQUEST['page'] ) && $this->settings_page == $_REQUEST['page'] ) {
				if( ( !is_network_admin() && is_multisite() ) || ( $this->is_multinetwork && !$this->_is_mn_settings_page ) ) {
					add_action( ( is_network_admin() ? 'network_admin_notices' : 'admin_notices' ), array( $this, 'options_override_message' ) );
				}
				wp_enqueue_script( 'ad-employee-list-admin' );
				wp_enqueue_style( 'ad-employee-list-admin-style' );
			}
			
		} /* __construct() */
		
		/**
		 * Perform any actions that need to happen in the admin area
		 */
		function _init_admin() {
			$this->_get_settings();
			
			register_setting( $this->settings_page, 	$this->settings_name, 	array( $this, '_sanitize_options' 	) );
			register_setting( $this->settings_page, 	$this->prefs_name, 		array( $this, '_sanitize_prefs' 	) );
			register_setting( $this->settings_page,		$this->output_name,		array( $this, '_sanitize_output_opts' ) );
			
			$this->add_settings_sections();
			
			if( function_exists( 'add_meta_box' ) ) {
				$this->_metaboxes = true;
				$this->add_meta_boxes();
			}
			
			$this->add_settings_fields();
		}
		
		/**
		 * Print an Admin Notice about lack of LDAP Support
		 */
		function _ldap_not_supported() {
			echo '<div class="error"><p><strong>' . __( 'LDAP Not Supported', $this->text_domain ) . '</strong></p><p>' . sprintf( __( 'Your PHP configuration does not appear to support LDAP connections; therefore, the %s plug-in will not work at all. It is recommended that you deactivate the plug-in until you are able to update your PHP configuration to support LDAP.', $this->text_domain ), $this->plugin_name ) . '</p></div>';
		} /* _ldap_not_supported() function */
		
		/**
		 * Check to see if this is installed/activated in a multi-network environment
		 */
		protected function _is_multinetwork() {
			if( function_exists( 'is_multinetwork' ) && is_multinetwork() )
				return $this->is_multinetwork = true;
			
			return false;
		} /* _is_multinetwork() */
		
		/**
		 * Retrieve the settings options for this site/network/multi-network
		 * Only retrieves the settings that have been explicitly set for the page
		 * 		currently being viewed. This does not recursively retrieve any
		 * 		settings that may be overridden. This function is only used to
		 * 		show which settings have been set within this admin page.
		 */
		protected function _get_settings() {
			$pre = '';
			if( $this->_is_mn_settings_page ) {
				$pre = 'mnetwork_';
			} elseif( is_network_admin() ) {
				$pre = 'site_';
			}
			
			$this->_settings	= stripslashes_deep( maybe_unserialize( call_user_func( "get_{$pre}option", $this->settings_name, array() ) ) );
			$this->_prefs		= stripslashes_deep( maybe_unserialize( call_user_func( "get_{$pre}option", $this->prefs_name,	array() ) ) );
			$this->_output_opts	= stripslashes_deep( maybe_unserialize( call_user_func( "get_{$pre}option", $this->output_name, array() ) ) );
			return;
		}
		
		/**
		 * Save any options that have been modified
		 * Only used in network admin and multinetwork settings. The Settings API handles saving options
		 * 		in the regular site admin area.
		 * @return array the output from each save action
		 * @uses active_directory_employee_list_admin::_set_options_network()
		 * @uses is_network_admin()
		 */
		protected function _set_options( $opt ) {
			if( !wp_verify_nonce( $_REQUEST['_wpnonce'], $this->settings_page . '-options' ) )
				return false;
			
			$output = array();
			if( is_network_admin() ) {
				$output[$this->settings_name] 	= $this->_set_options_network( $this->settings_name, 	$opt[$this->settings_name] 	);
				/*if( false === $output[$this->settings_name] && WP_DEBUG ) {
					print( "\n<!--\nSettings query:\n" );
					print $GLOBALS['wpdb']->last_query;
					print( "\n-->\n" );
				}*/
				$output[$this->prefs_name] 		= $this->_set_options_network( $this->prefs_name,		$opt[$this->prefs_name]		);
				
				$output[$this->output_name]		= $this->_set_options_network( $this->output_name,		$opt[$this->output_name]	);
				/*if( false === $output[$this->prefs_name] && WP_DEBUG ) {
					print( "\n<!--\nPreferences query:\n" );
					print $GLOBALS['wpdb']->last_query;
					print( "\n-->\n" );
				}*/
			}
			return $output;
		}
		
		/**
		 * Save a specific set of options for the network/multinetwork
		 *
		 * @param string $opt_name the name of the option to save
		 * @param mixed $opt the value to save
		 *
		 * @uses active_directory_employee_list_admin::_is_mn_settings_page to determine whether to save mnetworkmeta or sitemeta
		 * @uses update_mnetwork_option() to update the mnetwork meta if necessary
		 * @uses update_site_option() to update sitemeta if necessary
		 */
		protected function _set_options_network( $opt_name, $opt ) {
			if( $this->_is_mn_settings_page && function_exists( 'update_mnetwork_option' ) ) {
				return update_mnetwork_option( $opt_name, $opt );
			} else {
				return update_site_option( $opt_name, $opt );
			}
		}
		
		/**
		 * Clean up any option settings that need to be cleaned up before saving
		 */
		function _sanitize_options( $input ) {
			$this->_get_options();
			
			if( isset( $input['ignore_settings_group'] ) ) {
				$this->_sanitized = true;
				return false;
			}
			$this->_log( "\n<!-- The unsanitized opts look like:\n", $input, "\n and the AD password is: \n", $this->_ad_password, "\n-->\n" );
			
			if( $this->_sanitized )
				return $opts;
			
			$output = array();
			
			$output['_domain_controllers']	= explode( ';', str_replace( ' ', '', $input['_domain_controllers'] ) );
			$output['_base_dn'] 			= $input['_base_dn'];
			$output['_use_ssl'] 			= isset( $input['_use_ssl'] ) ? true : false;
			$output['_use_tls']				= isset( $input['_use_tls'] ) ? true : false;
			$output['_ad_port']				= empty( $input['_ad_port'] ) ? 389 : $input['_ad_port'];
			$output['_ad_username'] 		= $input['_ad_username'];
			$output['_ad_password'] 		= base64_encode( $this->_ad_password ) == $input['_ad_password'] ? $input['_ad_password'] : base64_encode( $input['_ad_password'] );
			$output['_account_suffix']		= empty( $input['_account_suffix'] ) ? '' : $input['_account_suffix'];
			
			$this->_sanitized = true;
			
			$this->_log( "\n<!-- The sanitized opts look like:\n", $output, "\n-->\n" );
			
			return array_map( 'stripslashes_deep', $output );
		}
		
		/**
		 * Clean up any preference settings that need to be sanitized before saving
		 */
		function _sanitize_prefs( $input ) {
			if( isset( $input['ignore_prefs_group'] ) ) {
				return false;
			}
			
			$output = array();
			
			$output['ad_group']				= empty( $input['ad_group'] ) ? null : $input['ad_group'];
			$output['fields_to_show']		= empty( $input['fields_to_show'] ) ? null : ( is_array( $input['fields_to_show'] ) ? $input['fields_to_show'] : explode( ';', str_replace( ' ', '', $input['fields_to_show'] ) ) );
			$output['results_per_page']		= empty( $input['results_per_page'] ) ? -1 : intval( $input['results_per_page'] );
			$output['order_by'] 			= empty( $input['order_by'] ) ? null : $input['order_by'];
			
			return array_map( 'stripslashes_deep', $output );
		}
		
		/**
		 * Clean up any output options that need to be sanitized before saving
		 */
		function _sanitize_output_opts( $input ) {
			if( isset( $input['ignore_output_group'] ) ) {
				return false;
			}
			
			$output = array_map( 'stripslashes_deep', $input );
			return $output;
		}
		
		/**
		 * Delete an option from the sitemeta table if appropriate
		 * This is just a wrapper function for the maybe_delete_option() function
		 * @uses active_directory_employee_list_admin::maybe_delete_option()
		 */
		function maybe_delete_site_option( $option, $value ) {
			return $this->maybe_delete_option( $option, $value, 'site' );
		}
		
		/**
		 * Delete an option from the options or sitemeta table if appropriate
		 */
		function maybe_delete_option( $option, $value, $type=null ) {
			if( false != $value )
				return;
			
			if( $this->settings_name !== $option && $this->prefs_name !== $option && $this->output_name !== $option )
				return;
			
			if( 'site' === $type )
				return delete_site_option( $option );
			else
				return delete_option( $option );
		}
		
		/**
		 * (no internal description)
		 * @deprecated
		 */
		function dump_sanitized_options() {
			/*var_dump( func_get_args() );
			die();
			exit;*/
		}
		
		/**
		 * Add the settings sections to the options page
		 */
		function add_settings_sections() {
			add_settings_section( $this->settings_name . '_group',	$this->settings_titles[$this->settings_name], 	array( &$this, 'settings_section' 	), $this->settings_page );
			add_settings_section( $this->prefs_name . '_group',		$this->settings_titles[$this->prefs_name],		array( &$this, 'prefs_section' 		), $this->settings_page );
			add_settings_section( $this->output_name . '_group',	$this->settings_titles[$this->output_name],		array( &$this, 'output_section'		), $this->settings_page );
		}
		
		/**
		 * Add the meta boxes to the options page
		 */
		function add_meta_boxes() {
			add_meta_box( 
				'meta-' . $this->settings_name,
				__( $this->settings_titles[$this->settings_name], $this->text_domain ), 
				array( $this, 'make_settings_meta_boxes' ), 
				$this->settings_page, 
				'normal', 
				'high', 
				array( 'id' => $this->settings_name )
			);
			add_meta_box( 
				'meta-' . $this->prefs_name,
				__( $this->settings_titles[$this->prefs_name], $this->text_domain ), 
				array( $this, 'make_settings_meta_boxes' ), 
				$this->settings_page, 
				'normal', 
				'high', 
				array( 'id' => $this->prefs_name )
			);
			add_meta_box( 
				'meta-' . $this->output_name,
				__( $this->settings_titles[$this->output_name], $this->text_domain ), 
				array( $this, 'make_settings_meta_boxes' ), 
				$this->settings_page, 
				'normal', 
				'high', 
				array( 'id' => $this->output_name )
			);
		}
		
		/**
		 * Set a meta box to be closed by default
		 * Will not work until WordPress 3.2 is released
		 */
		function close_metabox( $classes=array() ) {
			if( !in_array( 'closed', $classes ) )
				$classes[] = 'closed';
			
			return $classes;
		}
		
		/**
		 * Add all of the necessary settings fields to the options page
		 */
		function add_settings_fields() {
			$fields = $this->_get_settings_fields();
			
			if( ( is_admin() && !is_network_admin() && is_multisite() ) || ( is_network_admin() && $this->is_multinetwork && !$this->_is_mn_settings_page ) ){
				$ignore_text = __( '<strong>Ignore this group of options?</strong>', $this->text_domain );
				$fields[$this->settings_name]	= array_merge( array( 'ignore_settings_group' => $ignore_text ), $fields[$this->settings_name] );
				$fields[$this->prefs_name]		= array_merge( array( 'ignore_prefs_group' => $ignore_text ), $fields[$this->prefs_name] );
				$fields[$this->output_name]		= array_merge( array( 'ignore_output_group' => $ignore_text ), $fields[$this->output_name] );
			}
			
			foreach( $fields as $k=>$v ) {
				foreach( $v as $id=>$label ) {
					add_settings_field( 
						/*$id =*/ $id, 
						/*$title =*/ $label, 
						/*$callback =*/ array( &$this, 'build_field' ), 
						/*$page =*/ $this->settings_page, 
						/*$section =*/ $k . '_group', 
						/*$args =*/ array( 'label_for' => $id, 'section' => $k )
					);
				}
			}
		}
		
		/**
		 * Add the options page to the Site Admin Settings menu
		 */
		function admin_menu() {
			add_options_page(
				/*$page_title = */'Active Directory Employee List Options', 
				/*$menu_title = */'AD Employee List', 
				/*$capability = */'manage_options', 
				/*$menu_slug = */$this->settings_page, 
				/*$function = */array($this, 'display_admin_page')
			);
		}
		
		/**
		 * Add the options page to the Network Admin Settings menu.
		 * If this is a multinetwork setup and this is the primary network, add an options
		 * 		page to the Sites menu, too.
		 */
		function network_admin_menu() {
			if( $this->_is_primary_network ) {
				add_submenu_page(
					/*$parent_slug = */'sites.php', 
					/*$page_title = */'MultiNetwork Active Directory Employee List Options', 
					/*$menu_title = */'MultiNetwork AD List', 
					/*$capability = */'manage_networks', 
					/*$menu_slug = */$this->settings_page, 
					/*$function = */array($this, 'display_admin_page_mn')
				);
			}
			add_submenu_page(
				/*$parent_slug = */'settings.php', 
				/*$page_title = */'Active Directory Employee List Options', 
				/*$menu_title = */'AD Employee List', 
				/*$capability = */'manage_network_options', 
				/*$menu_slug = */$this->settings_page, 
				/*$function = */array($this, 'display_admin_page')
			);
		}
		
		/**
		 * Display the multinetwork admin settings page
		 * Wrapper for the display_admin_page() function
		 * @uses active_directory_employee_list_admin::display_admin_page()
		 */
		function display_admin_page_mn() {
			$this->display_admin_page(true);
		}
		
		/**
		 * Display the administrative settings page for this plugin
		 * @param null deprecated
		 *
		 * @uses active_directory_employee_list_admin::_no_permissions()
		 * @uses active_directory_employee_list_admin::_set_options()
		 * @uses active_directory_employee_list_admin::options_updated_message()
		 *
		 * @uses active_directory_employee_list::$text_domain
		 * @uses active_directory_employee_list_admin::$_is_mn_settings_page
		 * @uses active_directory_employee_list_admin::$settings_page
		 *
		 * @uses is_network_admin()
		 * @uses current_user_can()
		 * @uses is_admin()
		 * @uses wp_nonce_field()
		 * @uses do_meta_boxes()
		 * @uses settings_fields()
		 */
		function display_admin_page() {
			if( ( is_network_admin() && !current_user_can( 'manage_network_options' ) ) || ( is_admin() && !current_user_can( 'manage_options' ) ) )
				return $this->_no_permissions();
			
			if( is_network_admin() && isset( $_REQUEST['action'] ) && $this->settings_page == $_REQUEST['page'] ) {
				$msg = $this->_set_options( $_REQUEST );
			}
			
			$this->_get_options();
?>
	<div class="wrap">
<?php
			if( isset( $msg ) && is_array( $msg ) ) {
				$this->options_updated_message( $msg );
			}
?>
		<h2><?php _e( 'Active Directory Employee List Options', $this->text_domain ) ?></h2>
		<div id="poststuff" class="metabox-holder">
			<div id="post-body">
				<div id="post-body-content">
					<form method="post" action="<?php echo ( is_network_admin() ) ? '' : 'options.php'; ?>">
<?php
		settings_fields( $this->settings_page );
		if( $this->_is_mn_settings_page ) {
?>
						<input type="hidden" name="multinetwork" value="1"/>
<?php
		}
?>
						<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
						<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
                        
						<?php if( $this->_metaboxes ) { do_meta_boxes( $this->settings_page, 'normal', null ); } else { do_settings_sections( $this->settings_page ); } ?>
						<p><input type="submit" value="<?php _e( 'Save', $this->text_domain ) ?>" class="button-primary"/></p>
					</form>
				</div><!-- #post-body-content -->
			</div><!-- #post-body -->
		</div><!-- #poststuff --><br class="clear">
	</div><!-- .wrap -->
<?php
		}
		
		/**
		 * Output the code for the meta boxes
		 * @param array $opt information about the meta box being displayed
		 * @uses active_directory_employee_list::_get_options()
		 * @uses active_directory_employee_list_admin::_get_settings()
		 * @uses active_directory_employee_list_admin::$settings_page
		 */
		function make_settings_meta_boxes() {
			$this->_get_options();
			$this->_get_settings();
			$opt = func_get_args();
			$opt = array_pop( $opt );
			$id = str_replace( 'meta-', '', $opt['id'] );
			
			switch( $id ) {
				case $this->settings_name:
					$this->settings_section( 'h2' );
					break;
				case $this->prefs_name:
					$this->prefs_section( 'h2' );
					break;
				case $this->output_name:
					$this->output_section( 'h2' );
					break;
				default:
					printf( __( '<%1$s>This is a settings section with no description</%1$s>', $this->text_domain ), 'p' );
			}
?>
		<table class="form-table">
<?php
			do_settings_fields( $this->settings_page, $id . '_group' );
?>
		</table>
<?php
		}
		
		/**
		 * Build each individual form field for the options page
		 * @param array $args the arguments passed from the add_settings_field() function
		 * @uses active_directory_employee_list_admin::_get_field_details()
		 * @uses active_directory_employee_list_admin::$settings_name
		 * @uses active_directory_employee_list_admin::$_settings
		 * @uses active_directory_employee_list_admin::$_prefs
		 * @uses checked()
		 * @uses selected()
		 */
		function build_field( $args=array() ) {
			if( !array_key_exists( 'label_for', $args ) )
				return;
			
			$field = $this->_get_field_details( $args['label_for'] );
			
			if( !array_key_exists( 'default', $field ) )
				$field['default'] = false;
			
			if( !array_key_exists( 'type', $field ) )
				$field['type'] = 'text';
			
			if( $this->settings_name === $args['section'] )
				$value = isset( $this->_settings[$args['label_for']] ) ? $this->_settings[$args['label_for']] : false;
			elseif( $this->prefs_name === $args['section'] )
				$value = isset( $this->_prefs[$args['label_for']] ) ? $this->_prefs[$args['label_for']] : false;
			else/*if( $this->output_name === $args['section'] )*/
				$value = isset( $this->_output_opts[$args['label_for']] ) ? $this->_output_opts[$args['label_for']] : false;
			
			switch( $field['type'] ) {
				case 'checkbox':
?>
					<input type="checkbox" name="<?php echo $args['section'] ?>[<?php echo $args['label_for'] ?>]" id="<?php echo $args['label_for'] ?>" value="1"<?php checked( $field['default'], $value ) ?><?php echo array_key_exists( 'class', $field ) ? ' class="' . $field['class'] . '"' : '' ?>/>
<?php
				break;
				case 'select':
					if( array_key_exists( 'multiple', $field ) && $field['multiple'] ) {
						$multiple = true;
						$field['class'] = array_key_exists( 'class', $field ) && !empty( $field['class'] ) ? 
							$field['class'] . ' multiple' : 
							'multiple';
					} else {
						$multiple = false;
					}
?>
					<select<?php echo $multiple ? ' multiple="multiple"' : '' ?> name="<?php echo $args['section'] ?>[<?php echo $args['label_for'] ?>]<?php echo $multiple ? '[]' : '' ?>" id="<?php echo $args['label_for'] ?>"<?php echo array_key_exists( 'class', $field ) ? ' class="' . $field['class'] . '"' : '' ?>>
<?php
					if( !$multiple ) {
?>
                    	<option<?php selected( $field['default'], $value ) ?>><?php _e( '-- Please select an option --' ) ?></option>
<?php
						foreach( $field['options'] as $val=>$lbl ) {
?>
						<option value="<?php echo $val ?>" title="<?php echo $lbl ?>"<?php selected( $value, $val ) ?>><?php echo $lbl ?></option>
<?php
						}
					} else {
						foreach( $field['options'] as $val=>$lbl ) {
							$s = is_array( $value ) && in_array( $val, $value ) ? ' selected="selected"' : '';
?>
						<option value="<?php echo $val ?>" title="<?php echo $lbl ?>"<?php echo $s ?>><?php echo $lbl ?></option>
<?php
						}
					}
?>
                    </select>
<?php
				break;
				case 'textarea':
?>
					<textarea rows="10" cols="45" name="<?php echo $args['section'] ?>[<?php echo $args['label_for'] ?>]" id="<?php echo $args['label_for'] ?>"<?php echo array_key_exists( 'class', $field ) ? ' class="' . $field['class'] . '"' : '' ?> placeholder="<?php echo $field['default'] ?>"><?php echo esc_textarea( $value ) ?></textarea>
<?php
				break;
				default:
?>
					<input class="<?php echo array_key_exists( 'class', $field ) ? $field['class'] : 'widefat' ?>" type="<?php echo $field['type'] ?>" name="<?php echo $args['section'] ?>[<?php echo $args['label_for'] ?>]" id="<?php echo $args['label_for'] ?>" value="<?php echo is_array( $value ) ? implode( ';', $value ) : esc_attr( $value ) ?>" placeholder="<?php echo esc_attr( $field['default'] ) ?>"/>
<?php
			}
			if( array_key_exists( 'note', $field ) )
				echo '<div class="note">' . $field['note'] . '</div>';
			if( array_key_exists( 'hidenote', $field ) ) {
				echo '<p><a href="#hidenote-' . $args['label_for'] . '" class="adel-reveal-if-js">' . __( 'More information', $this->text_domain ) . '</a></p>';
				echo '<div class="adel-hide-if-js" id="hidenote-' . $args['label_for'] . '">' . $field['hidenote'] . '</div>';
			}
		}
		
		protected function _get_settings_fields( $group='all' ) {
			$fields = array(
				$this->settings_name	=> array(
					'_domain_controllers'	=> __( 'Domain controllers:', $this->text_domain ), 
					'_base_dn'				=> __( 'Base DN:', $this->text_domain ), 
					'_use_ssl'				=> __( 'Use an SSL connection?', $this->text_domain ), 
					'_use_tls'				=> __( 'Use TLS after binding to LDAP?', $this->text_domain ), 
					'_ad_port'				=> __( 'Port on which Active Directory listens:', $this->text_domain ), 
					'_ad_username'			=> __( 'Bind user:', $this->text_domain ), 
					'_ad_password'			=> __( 'Bind user password:', $this->text_domain ), 
					'_account_suffix'		=> __( 'Account suffix for bind user:', $this->text_domain ), 
				),
				$this->prefs_name		=> array(
					'ad_group'				=> __( 'The Active Directory group to retrieve:', $this->text_domain ), 
					'fields_to_show'		=> __( 'Which AD fields should be displayed in the list?', $this->text_domain ), 
					'results_per_page'		=> __( 'How many results should be shown on a page?', $this->text_domain ),
					'order_by'				=> __( 'Sort the list according to which field?', $this->text_domain ),
				),
				$this->output_name		=> array(
					'before_list'			=> __( 'Before the employee list:', $this->text_domain ), 
					'after_list'			=> __( 'After the employee list:', $this->text_domain ), 
					'after_title'			=> __( 'After the list title:', $this->text_domain ), 
					'title_wrap'			=> __( 'HTML element to wrap the list title:', $this->text_domain ), 
					'title_class'			=> __( 'The list title CSS class:', $this->text_domain ), 
					'title_id'				=> __( 'The HTML ID for the list title:', $this->text_domain ), 
					'title'					=> __( 'The list title:', $this->text_domain ), 
					'list_wrap'				=> __( 'HTML element to wrap the list:', $this->text_domain ), 
					'list_class'			=> __( 'The list CSS class:', $this->text_domain ), 
					'list_id'				=> __( 'The HTML ID for the list:', $this->text_domain ), 
					'item_wrap'				=> __( 'The HTML element to wrap each list item:', $this->text_domain ), 
					'item_class'			=> __( 'The CSS class for each list item:', $this->text_domain ), 
					'item_id'				=> __( 'The HTML ID for each list item:', $this->text_domain ), 
					'prev_page_link'		=> __( 'Previous page link:', $this->text_domain ), 
					'next_page_link'		=> __( 'Next page link:', $this->text_domain ), 
					'output_builder'		=> __( 'List item output builder:', $this->text_domain ), 
				),
			);
			return 'all' === $group ? $fields : $fields[$group];
		}
		
		/**
		 * Retrieve information about the fields being displayed
		 */
		protected function _get_field_details( $field ) {
			$this->_get_options();
			
			if( empty( $this->_settings_fields ) ) {
				$template_tags = $this->get_template_tags( false );
				foreach( $template_tags as $k=>$v ) {
					$template_tags[$k] = $k . ': ' . $v;
				}
				
				$ignore_text = sprintf( __( 'Check this box if you would like the %s settings for this options group to override any settings entered here.', $this->text_domain ), ( is_network_admin() ? 'multi-network' : 'network' ) );
				$this->_settings_fields = array(
					'ignore_settings_group' => array(
						'note'		=> $ignore_text,
						'type'		=> 'checkbox',
						'default'	=> $this->_are_settings_empty( $this->settings_name) ? false : 1,
					),
					'ignore_prefs_group' 	=> array(
						'note'		=> $ignore_text,
						'type'		=> 'checkbox',
						'default'	=> $this->_are_settings_empty( $this->prefs_name) ? false : 1,
					),
					'ignore_output_group'	=> array(
						'note'		=> $ignore_text, 
						'type'		=> 'checkbox', 
						'default'	=> $this->_are_settings_empty( $this->output_name) ? false : 1,
					),
					/* AD Connection settings */
					'_domain_controllers'	=> array( 
						'note' 		=> __( 'Separate with semicolons, e.g. "dc1.domain.tld;dc2.domain.tld".', $this->text_domain ), 
						'default'	=> null,
					),
					'_base_dn'				=> array( 
						'note' 		=> __( 'e.g., "ou=unit,dc=domain,dc=tld"', $this->text_domain ), 
						'default'	=> null,
					),
					'_use_ssl'				=> array( 
						'note' 		=> __( 'Bind to the LDAP server using an secure socket layer (SSL) connection. When using SSL, a port of 636 will always be used, no matter what is specified in the "port" setting below.', $this->text_domain ), 
						'type' 		=> 'checkbox', 
						'default'	=> true,
					),
					'_use_tls'				=> array( 
						'note' 		=> __( 'Secure the connection between the WordPress and the Active Directory Servers using <strong>TLS</strong> after the bind occurs.', $this->text_domain ), 
						'type' 		=> 'checkbox', 
						'default'	=> true,
					),
					'_ad_port'				=> array( 
						'note' 		=> __( 'Defaults to 389', $this->text_domain ), 
						'class'		=> 'num', 
						'default'	=> 389
					),
					'_ad_username'			=> array( 
						'note'		=> __( 'Username for non-anonymous requests to AD (e.g. "ldapuser@domain.tld"). Leave empty for anonymous requests.', $this->text_domain ), 
						'default'	=> null,
					),
					'_ad_password'			=> array(
						'type'		=> 'password', 
						'default'	=> null,
					),
					'_account_suffix'		=> array(
						'note'		=> __( 'If an account suffix (e.g. "@mydomain.local") needs to be appended to the bind user before it can be authenticated, enter that suffix here.', $this->text_domain ), 
						'default'	=> null,
					),
					/* List Preferences */
					'ad_group'				=> $this->get_ad_group_field_details(),
					'fields_to_show'		=> array(
						'default'	=> array( 'displayname', 'givenname', 'cn', 'mail', 'telephonenumber', 'department' ),
						'type'		=> 'select',
						'options'	=> $template_tags,
						'multiple'	=> true,
						'class'		=> 'widefat',
					),
					'results_per_page'		=> array(
						'default'	=> 25,
						'class'		=> 'narrow',
						'note'		=> 'Leaving this field blank will cause the plugin to attempt to display all retrieved results in one list. Otherwise, the results will be paginated with this many results showing on each page.',
					),
					'order_by'				=> array(
						'type'		=> 'select',
						'options'	=> $template_tags,
						'class'		=> 'widefat',
						'note'		=> 'If this option is not set, the list will be returned in the order it is retrieved from the AD server.',
					),
					/* Output options */
					'before_list'			=> array(
						'default'	=> '',
						'note'		=> __( 'Any HTML code you would like to appear before the list of employees. This code is output before the opening title_wrap tag.', $this->text_domain ), 
						'type'		=> 'textarea',
						'class'		=> 'large-text',
					),
					'after_list'			=> array(
						'default'	=> '',
						'note'		=> __( 'Any HTML code you would like to appear after the list of employees. This code is output after the closing list_wrap tag.', $this->text_domain ), 
						'type'		=> 'textarea',
						'class'		=> 'large-text',
					),
					'after_title'			=> array(
						'default'	=> '',
						'note'		=> __( 'Any HTML code you would like to appear between the closing title_wrap tag and the opening list_wrap tag.', $this->text_domain ), 
						'type'		=> 'textarea',
						'class'		=> 'large-text',
					),
					'title_wrap'			=> array(
						'default'	=> 'h2',
						'note'		=> __( 'The HTML element you would like to use to wrap the list title (if set). Just the element name, please; no opening or closing brackets.', $this->text_domain ), 
					),
					'title_class'			=> array(
						'default'	=> 'adel-title',
						'note'		=> __( 'The CSS class you would like applied to the list title (if set). If you would prefer that no CSS class be applied to the title, leave this blank.', $this->text_domain ), 
					),
					'title_id'				=> array(
						'default'	=> '',
						'note'		=> __( 'If you would like to apply an HTML ID to the list title, you can indicate that here. Remember that IDs should be unique, so, if you plan on using multiple employee lists on a single page, you should leave this blank.', $this->text_domain ), 
					),
					'title'					=> array(
						'default'	=> __( 'List of Employees', $this->text_domain ), 
						'note'		=> __( 'The title you would like to appear at the top of the list. The title is output prior to the opening of the list itself.', $this->text_domain ), 
					),
					'list_wrap'				=> array(
						'default'	=> 'ul', 
						'note'		=> __( 'The HTML element you would like to use to wrap the entire list. Just the element name, please; no opening or closing brackets.', $this->text_domain ), 
					),
					'list_class'			=> array(
						'default'	=> 'adel-list',
						'note'		=> __( 'The CSS class you would like to assign to the opening list_wrap tag, aiding in styling the entire list. If you would prefer that no CSS class be applied to the list, leave this blank.', $this->text_domain ), 
					),
					'list_id'				=> array(
						'default'	=> '',
						'note'		=> __( 'If you would like to apply an HTML ID to the list itself, you can indicate that here. Remember that IDs should be unique, so, if you plan on using multiple employee lists on a single page, you should leave this blank.', $this->text_domain ), 
					),
					'item_wrap'				=> array(
						'default'	=> 'li',
						'note'		=> __( 'The HTML element you would like to use to wrap each individual employee in the list. Just the element name, please; no opening or closing brackets.', $this->text_domain ), 
					),
					'item_class'			=> array(
						'default'	=> 'adel-list-item',
						'note'		=> __( 'The CSS class you would like to assign to each individual employee in the list. If you would prefer that no CSS class be applied to the list, leave this blank.', $this->text_domain ), 
					),
					'item_id'				=> array(
						'default'	=> 'adel-list-item-%samaccountname%',
						'note'		=> __( 'If you would like to apply an HTML ID to each individual employee in the list, you can indicate that here. You can use placeholder variables for user information (any of the fields that are set to be retrieved, plus the user\'s username (samaccountname). Simply wrap the placeholder variable with percent symbols (so, to use a placeholder for samaccountname, use %samaccountname%) All disallowed characters (the @ symbol, dots, spaces, etc.) will be replaced with hyphens. Remember that IDs should be unique, so, if you plan on using multiple employee lists that may include the same employee multiple times on a single page, you should leave this blank. Likewise, you should use a placeholder variable that will be unique.', $this->text_domain ), 
					),
					'prev_page_link'		=> array(
						'default'	=> '<span class="previous-page"><a href="%link%">Previous page</a></span>',
						'note'		=> 'Please provide the HTML code you would like to use as the link to previous pages of results. You should use the %link% keyword where you would like the URL to appear. If this field is left blank, the link will not appear on the page at all.',
					),
					'next_page_link'		=> array(
						'default'	=> '<span class="next-page"><a href="%link%">Next page</a></span>',
						'note'		=> 'Please provide the HTML code you would like to use as the link to the next pages of results. You should use the %link% keyword where you would like the URL to appear. If this field is left blank, the link will not appear on the page at all.',
					),
					'output_builder'		=> array(
						'default'	=> '&lt;article id=&quot;adel-employee-%samaccountname%&quot;&gt; &lt;p&gt; [if mail] &lt;a href=&quot;mailto:%mail%&quot;&gt;%displayname%&lt;/a&gt; [else] %displayname% [endif] &lt;br/&gt; %telephonenumber% &lt;br/&gt; %department% &lt;/p&gt; &lt;/article&gt;', 
						'note'		=> __( 'Please indicate how you would like the information for each individual employee to be output within its list item wrapper. If this is left blank, the employee\'s information will simply be wrapped in a <code>&lt;ul&gt;</code> element with each field retrieved from the database being wrapped in <code>&lt;li&gt;</code> elements.', $this->text_domain ), 
						'type'		=> 'textarea', 
						'class'		=> 'large-text',
						'hidenote'	=> $this->get_output_builder_instructions(),
					),
				);
			}
			
			return $this->_settings_fields[$field];
		}
		
		/**
		 * Retrieve specific information about the ad_group field
		 */
		function get_ad_group_field_details() {
			$rt = array(
				'note'		=> __( 'If you would like to retrieve only members of a specific AD group, enter that group name here.', $this->text_domain ), 
				'default'	=> false,
			);
			$g = $this->get_all_groups();
			if( !empty( $g ) ) {
				$rt['type'] = 'select';
				$rt['options'] = $g;
			} else {
				$rt['type'] = 'text';
			}
			
			return $rt;
		}
		
		/**
		 * Output the description of the settings section
		 */
		function settings_section( $wrap='p' ) {
			if( empty( $wrap ) )
				$wrap = 'p';
				
			printf( __( '<%1$s>This is the section where options are set for connecting to the active directory server.</%1$s>', $this->text_domain ), $wrap );
		}
		
		/**
		 * Output the description for the preferences section
		 */
		function prefs_section( $wrap='p' ) {
			if( empty( $wrap ) )
				$wrap = 'p';
				
			printf( __( '<%1$s>This is the section where you set options as to what gets displayed in the employee list.</%1$s>', $this->text_domain ), $wrap );
		}
		
		/**
		 * Output the description for the output section
		 */
		function output_section( $wrap='p' ) {
			if( empty( $wrap ) )
				$wrap = 'p';
				
			printf( __( '<%1$s>This is the section where you set any preferences/options for the way the employee list is output on the page/post.</%1$s>', $this->text_domain ), $wrap );
		}
		
		/**
		 * Output the appropriate error message about the user not having the right permissions
		 */
		protected function _no_permissions() {
?>
	<div class="wrap">
		<h2><?php _e( 'Active Directory Settings', $this->text_domain ) ?></h2>
		<p><?php _e( 'You do not have the appropriate permissions to update these options. Please work with an administrator of the site to update the options. Thank you.', $this->text_domain ) ?></p>
	</div>
<?php
		}
		
		/**
		 * Output a message indicating whether or not the options were updated
		 */
		protected function options_updated_message( $msg ) {
?>
		<div class="updated fade">
<?php
				foreach( $msg as $k=>$v ) {
					printf( __( '<p>The %s options were %supdated%s.</p>' ), $this->settings_titles[$k], ( true === $v ? '' : '<strong>not</strong> ' ), ( true === $v ? ' successfully' : '' ) );
				}
?>
        </div>
<?php
		}
		
		/**
		 * Output a message indicating whether the current settings will override other settings
		 */
		function options_override_message() {
?>
		<div class="updated fade">
<?php
			if( is_network_admin() && $this->is_multinetwork && !$this->_is_mn_settings_page ) {
				if( !$this->_is_primary_network ) {
					$url = get_blog_details( 1 )->siteurl . '/wp-admin/network/' . 'sites.php?page=' . $this->settings_page;
				} else {
					$url = network_admin_url( 'sites.php?page=' . $this->settings_page );
				}
				
				printf( __( '<p>Any options you set and save on this screen will override the global multi-network options that were set on the <a href="%s">multi-network options page</a>.</p><p>To avoid overriding a specific set of options, please check the "<strong>%s</strong>" box in the appropriate settings section.</p>', $this->text_domain ), $url, __( 'Ignore this group of options?', $this->text_domain ) );
			} elseif( !is_network_admin() && is_multisite() ) {
				$url = network_admin_url( 'settings.php?page=' . $this->settings_page );
				printf( __( '<p>Any options you set and save on this screen will override the network options that were set on the <a href="%s">network options page</a>.</p><p>To avoid overriding a specific set of options, please check the "<strong>%s</strong>" box in the appropriate settings section.</p>', $this->text_domain ), $url, __( 'Ignore this group of options?', $this->text_domain ) );
			}
?>
        </div>
<?php
		}
		
		/**
		 * Checks to see if the specified block of options is empty or not
		 */
		function _are_settings_empty( $settings_name=null ) {
			switch( $settings_name ) {
				case $this->prefs_name:
					$val = $this->_prefs;
					break;
				case $this->output_name:
					$val = $this->_output_opts;
					break;
				default:
					$val = $this->_settings;
					break;
			}
			if( empty( $val ) )
				return true;
			
			if( is_array( $val ) || is_object( $val ) ) {
				foreach( $val as $v ) {
					if( !empty( $v ) )
						return false;
				}
				return true;
			}
			
			return false;
		}
		
	} /* active_directory_employee_list_admin class */
} /* if class_exists( active_directory_employee_list_admin ) statement */
?>