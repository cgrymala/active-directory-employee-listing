<?php
if( !class_exists( 'active_directory_employee_list' ) ) {
	class active_directory_employee_list {
		/**
		 * The base string to be used to query the AD server
		 * @var string
		 * @default null
		 */
		protected $_base_dn				= null;
		/**
		 * The list of domain controllers against which to authenticate
		 * @var string|array
		 * @default null
		 */
		protected $_domain_controllers	= null;
		/**
		 * The user string used to bind to the AD server
		 * @var string
		 * @default null
		 */
		protected $_ad_username			= null;
		/**
		 * The password for the bind user
		 * @var string
		 * @default null
		 */
		protected $_ad_password			= null;
		/**
		 * Whether or not to use SSL to connect to AD
		 * @var bool
		 * @default false
		 */
		protected $_use_ssl				= false;
		/**
		 * Whether or not to use TLS after binding with AD
		 * @var bool
		 * @default false
		 */
		protected $_use_tls 				= false;
		/**
		 * The account suffix to append to the bind user
		 * @var string
		 * @default null
		 */
		protected $_account_suffix 		= null;
		
		/**
		 * An AD group to use to filter the results
		 * @var string
		 * @default null
		 */
		var $ad_group					= null;
		/**
		 * The AD fields to retrieve and display in the list
		 * @var array
		 * @default null
		 */
		var $fields_to_show				= array( 'displayname', 'givenname', 'sn', 'mail', 'telephonenumber', 'department' );
		
		/**
		 * A static string holding the key to the settings options stored in the database
		 * @var string
		 */
		var $settings_name				= 'ad_employee_list_settings';
		/**
		 * A static string holding the key to the preferences options stored in the database
		 * @var string
		 */
		var $prefs_name					= 'ad_employee_list_prefs';
		/**
		 * A static string holding the key to the output options stored in the database
		 * @var string
		 */
		var $output_name				= 'ad_employee_list_output_opts';
		/**
		 * A static string holding the name of the settings page for WordPress
		 * @var string
		 */
		var $settings_page				= 'ad_employee_list_options_pg';
		/**
		 * A static string holding the name of the text domain used within this plugin
		 * @var string
		 */
		var $text_domain				= 'ad_employee_list_text_domain';
		/**
		 * A system variable to hold the base path to this plugin file
		 * @var string
		 */
		var $basepath					= null;
		/**
		 * The amount of time for which a transient option should be stored in the WordPress database
		 * @var string
		 */
		protected $transient_timeout	= 0;
		/**
		 * The adLDAP object used to authenticate against and query the server
		 * @private
		 * @var adLDAP
		 * @default null
		 */
		protected $ldap					= null;
		
		/**
		 * A static string to hold the title of this plugin
		 * @var string
		 */
		var $plugin_name				= 'Active Directory Employee List';
		/**
		 * Whether or not to output debug messages
		 * @var bool
		 */
		protected $_print_debug			= false;
		/**
		 * A container for debug messages
		 * @var string
		 */
		protected $_debug				= '';
		
		/**
		 * Any HTML code that should appear at the very beginning of the output
		 * @var string
		 * @default null
		 */
		var $before_list 				= null;
		/**
		 * The HTML element that should be used to wrap the title
		 * @var string
		 * @default 'h2'
		 */
		var $title_wrap 				= 'h2';
		/**
		 * The CSS class that should be used for the title
		 * @var string
		 * @default null
		 */
		var $title_class 				= null;
		/**
		 * The HTML ID that should use for the title
		 * @var string
		 * @default null
		 */
		var $title_id					= null;
		/**
		 * The string that should be used as the title
		 * @var string
		 * @default 'Employee List'
		 */
		var $title 						= 'List of Employees';
		/**
		 * Any HTML code that should appear between the title and the list
		 * @var string
		 * @default null
		 */
		var $after_title 				= null;
		/**
		 * The HTML element that should be wrapped around the entire list
		 * @var string
		 * @default 'ul'
		 */
		var $list_wrap 					= 'ul';
		/**
		 * The CSS class of the list
		 * @var string
		 * @default null
		 */
		var $list_class 				= null;
		/**
		 * The HTML ID for the list itself
		 * @var string
		 * @default null
		 */
		var $list_id 					= null;
		/**
		 * The HTML element that should be wrapped around the individual list items
		 * @var string
		 * @default 'li'
		 */
		var $item_wrap 					= 'li';
		/**
		 * The CSS class of the individual list items
		 * @var string
		 * @default null
		 */
		var $item_class 				= null;
		/**
		 * The HTML ID for each individual list item
		 * @var string
		 * @default null
		 */
		var $item_id 					= null;
		/**
		 * Any HTML code that should appear at the very end of the output
		 * @var string
		 * @default null
		 */
		var $after_list 				= null;
		/**
		 * The output template for an individual item in the list
		 * @var string
		 */
		var $output_builder				= '<article id="adel-employee-%samaccountname%"> <p> [if mail] <a href="mailto:%mail%">%givenname% %sn% </a> [elseif displayname] %displayname% [else] %givenname% %sn% [endif] [if telephonenumber]<br/> %telephonenumber% [endif][if department]<br/> %department% [endif]</p> </article>';
		
		/**
		 * Build our object
		 */
		function __construct() {
			$this->basepath = str_replace( array( basename( __FILE__ ), basename( dirname( __FILE__ ) ) ), '', realpath( __FILE__ ) );
			$this->_set_transient_timeout( 24*60*60 );
			$this->_get_options();
			
			add_action( 'init', array( &$this, '_init' ) );
			
			wp_register_script( 'ad-employee-list-admin', plugins_url( 'js/active-directory-employee-list.admin.js', dirname( __FILE__ ) ), array( 'jquery', 'post' ), '0.1a', true );
			wp_register_style( 'ad-employee-list-admin-style', plugins_url( 'css/active-directory-employee-list.admin.css', dirname( __FILE__ ) ), array( 'widgets' ), '0.1a', 'all' );
		}
		
		/**
		 * Perform any actions that need to happen upon WordPress init
		 */
		function _init() {
			if( !class_exists( 'adLDAPE' ) )
				require_once( $this->basepath . '/inc/adLDAP-extended.php' );
		}
		
		function register_widget() {
			return register_widget( 'active_directory_employee_list_widget' );
		}
		
		/**
		 * Check for the existence of an action before adding it
		 */
		function add_action( $tag, $callback ) {
			if( !has_action( $tag, $callback ) )
				add_action( $tag, $callback );
		}
		
		/**
		 * Check for the existence of a filter before adding it
		 */
		function add_filter( $tag, $callback ) {
			if( !has_filter( $tag, $callback ) )
				add_filter( $tag, $callback );
		}
		
		/**
		 * Add information/output to the error log
		 */
		protected function _log() {
			if( !$this->_print_debug )
				return;
			
			error_log( "\n<!-- Debug output from the Active Directory Employee List plugin: -->\n" );
			
			$args = func_get_args();
			foreach( $args as $arg ) {
				if( is_string( $arg ) )
					error_log( $arg );
				else
					error_log( print_r( $arg, true ) );
			}
			
			error_log( "\n<!-- End output from the Active Directory Employee List plugin -->\n" );
		}
		
		/**
		 * Set the _transient_timeout property of this object
		 * Determine how long transients should be stored in the database
		 */
		protected function _set_transient_timeout( $t=60 ) {
			$this->_transient_timeout = $t;
		}
		
		/**
		 * Retrieve the settings and preferences for this plugin.
		 * Recursively retrieves options from the current site, then
		 * 		network, then multi-network. If none exist, false is
		 * 		returned.
		 * Sets each appropriate object property with the value retrieved
		 * 		from the database.
		 * @return bool|array Returns false if no options are retrieved, returns
		 * 		an array of the retrieved options if they do
		 *
		 * @uses active_directory_employee_list::get_option()
		 * @uses active_directory_employee_list::_format_options()
		 * @uses maybe_unserialize()
		 */
		function _get_options() {
			$g_opt = $this->get_option( $this->settings_name, false );
			$opt = $this->get_option( $this->prefs_name, false );
			$o_opt = $this->get_option( $this->output_name, false );
			
			if( is_array( $opt ) && is_array( $g_opt ) )
				$opt = array_merge( $g_opt, $opt );
			elseif( !is_array( $opt ) )
				$opt = $g_opt;
			
			if( is_array( $opt ) && is_array( $o_opt ) )
				$opt = array_merge( $o_opt, $opt );
			elseif( !is_array( $opt ) )
				$opt = $o_opt;
			
			if( is_array( $opt ) ) {
				$opt = $this->_format_options( $opt );
				foreach( $opt as $k=>$v ) {
					if( property_exists( $this, $k ) )
						$this->$k = maybe_unserialize( $v );
				}
			}
			return $opt;
		}
		
		/**
		 * Retrieve a set of options from the database
		 * If the options exist in the options table, those are retrieved. If not, the options 
		 * 		are retrieved from the sitemeta table. If the options don't exist there, either, 
		 * 		and the get_mnetwork_option function exists, an attempt is made to retrieve them 
		 * 		from there.
		 *
		 * @param string $optname the name of the option to be retrieved
		 * @param mixed the default value to return is no options are retrieved
		 * @return mixed either the retrieved option or the default param
		 *
		 * @uses get_option() to retrieve options from the options table
		 * @uses get_site_option() to retrieve options from the sitemeta table
		 * @uses get_mnetwork_option() to retrieve options from the mnetwork_meta table (if exists)
		 * @uses maybe_unserialize() to possibly unserialize the returned result (though, that 
		 * 		should occur within each of the individual functions to get the options)
		 */
		protected function get_option( $optname, $default=false ) {
			if( $default === ( $opt = get_option( $optname, $default ) ) ) {
				$this->_log( "\n<!-- The blog-level option returned ", $opt, " and the default is set to ", $default , " -->\n" );
				
				if( $default === ( $opt = get_site_option( $optname, $default ) ) ) {
					$this->_log( "\n<!-- The network level option returned ", $opt, " and the default is set to ", $default, " -->\n" );
					if( function_exists( 'get_mnetwork_option' ) ) {
						$opt = get_mnetwork_option( $optname, $default );
						$this->_log( "\n<!-- The multi-network level option returned ", $opt, " and the default is set to ", $default, " -->\n" );
					} else {
						$this->_log( "\n<!-- The get_mnetwork_option function does not appear to exist. -->\n" );
					}
				} else {
					$this->_log( "\n<!-- The options were retrieved at the network level and looked like:\n", $opt, " and the default is set to ", $default, " -->\n" );
				}
			} else {
				$this->_log( "\n<!-- The options were retrieved at the blog level and looked like:\n", $opt, " and the default is set to ", $default, " -->\n" );
			}
			
			$opt = stripslashes_deep( maybe_unserialize( $opt ) );
			
			$this->_log( "\n<!-- The retrieved options look like:\n", $opt, "\n-->\n" );
			
			return $opt;
		}
		
		/**
		 * Formats any options after being retrieved from the database for use by the plugin
		 */
		protected function _format_options( $opts ) {
			if( is_array( $opts ) && array_key_exists( '_ad_password', $opts ) )
				$opts['_ad_password'] = base64_decode( $opts['_ad_password'] );
			
			return $opts;
		}
		
		/**
		 * Instantiate our adLDAPE object and perform initial bind
		 */
		function open_ldap() {
			if( is_object( $this->ldap ) )
				return true;
			
			$this->_log( "\n<!-- Preparing to open a connection to the AD server. -->\n" );
			
			try {
				$this->ldap = new adLDAPE( array(
					'base_dn'				=> $this->_base_dn,
					'domain_controllers'	=> $this->_domain_controllers,
					'ad_username'			=> $this->_ad_username,
					'ad_password'			=> $this->_ad_password,
					'use_ssl'				=> $this->_use_ssl,
					'use_tls'				=> $this->_use_tls,
					'account_suffix'		=> $this->_account_suffix,
				) );
			} catch( Exception $e ) {
				return $e->getMessage();
			}
			return true;
		}
		
		/**
		 * Retrieve a list of all available AD groups
		 */
		function get_all_groups() {
			$transname = 'adel_available_groups';
			if( is_network_admin() && ( false !== ( $g = get_site_transient( $transname ) ) ) )
				return $g;
			elseif( is_admin() && ( false !== ( $g = get_transient( $transname ) ) ) )
				return $g;
			
			if( true === $this->open_ldap() ) {
				try{
					$g = $this->ldap->search_groups( null, true, '*', true, 'cn', 'description' );
				} catch( Exception $e ) {
					$g = null;
				}
			} else {
				return null;
			}
			
			if( is_network_admin() )
				set_site_transient( $transname, $g, $this->transient_timeout );
			elseif( is_admin() )
				set_transient( $transname, $g, $this->transient_timeout );
			
			return $g;
		}
		
	}
}
?>