<?php
if( !class_exists( 'active_directory_employee_list' ) )
	require_once( 'class-active-directory-employee-list.php' );

if( !class_exists( 'active_directory_employee_list_output' ) ) {
	class active_directory_employee_list_output extends active_directory_employee_list {
		/**
		 * The parsed output template for an individual item in the list
		 * @var string
		 * @default null
		 */
		var $output_built				= null;
		/**
		 * An array of the template tags that will be replaced
		 * @var array
		 * @default array()
		 */
		var $_replacement_tags 			= array();
		
		function _init() {
			parent::_init();
			$this->add_shortcode();
		}
		
		/**
		 * Actually output the employee list
		 * @param string $group the name of the AD group to retrieve (null means all people will be retrieved)
		 * @param array $fields an array of the fields to retrieve (empty means the default $fields_to_show property is used)
		 * @param array $formatting an array of formatting options. You can include any of the formatting options in this 
		 * 		array to replace the saved options.
		 * @return array an array of the formatted list items
		 *
		 * @uses active_directory_employee_list::_get_options()
		 * @uses active_directory_employee_list::$output_builder
		 * @uses active_directory_employee_list::_get_default_output()
		 * @uses active_directory_employee_list::$output_built
		 * @uses active_directory_employee_list::_parse_template()
		 * @uses active_directory_employee_list::$ad_group
		 * @uses active_directory_employee_list::$fields_to_show
		 * @uses active_directory_employee_list::open_ldap()
		 * @uses active_directory_employee_list::get_employees()
		 * @uses active_directory_employee_list::$employee_list
		 * @uses active_directory_employee_list::_replace_tags()
		 */
		function list_employees( $group=null, $fields=array(), $formatting=array() ) {
			$this->_get_options();
			
			$this->_extract_formats( $formatting );
			
			if( empty( $this->output_builder ) )
				$this->_get_default_output();
			
			$this->output_built = $this->_parse_template( $this->output_builder );
			$this->_log( "\n<!-- Built output looks like: \n", $this->output_built, "\n and output builder looks like: \n", $this->output_builder, "\n-->\n" );
			
			if( !is_null( $group ) )
				$this->ad_group = $group;
			if( !empty( $fields ) )
				$this->fields_to_show = $fields;
			
			$output = array();
			
			if( isset( $_GET['adeq'] ) && !empty( $_GET['adeq'] ) ) {
				$employees = $this->search_employees( $_GET['adeq'], $fields, $group );
			} else {
				$employees = $this->get_employees();
			}
			if( empty( $employees ) )
				return array( 'noresults' => __( 'No employees could be found matching the criteria specified', $this->text_domain ) );
			
			foreach( $employees as $username=>$e ) {
				$output[$username] = $this->_replace_tags( $e, $username );
			}
			
			return $output;
		}
		
		/**
		 * Retrieve the array of formatted employees and outputs it on the screen
		 * @param string $group the name of the AD group to retrieve (null means all people will be retrieved)
		 * @param array $fields an array of the fields to retrieve (empty means the default $fields_to_show property is used)
		 * @param array $formatting an array of formatting options. You can include any of the formatting options in this 
		 * 		array to replace the saved options.
		 * @param bool $echo whether or not to echo the output
		 * @return mixed either echos the output or returns it
		 *
		 * @uses active_directory_employee_list::list_employees() to retrieve the array of employees
		 * @uses active_directory_employee_list::_show_title() to display the title area
		 * @uses active_directory_employee_list::_show_open_list() to open the employee list
		 * @uses active_directory_employee_list::_show_list() to display the list itself
		 * @uses active_directory_employee_list::_show_close_list() to close the list and display any HTML after
		 */
		function show_employees( $group=null, $fields=array(), $formatting=array(), $echo=true ) {
			$employees = $this->list_employees( $group, $fields, $formatting );
			
			$output = $this->_show_title( $echo ); /* before_list, title_wrap, title, after_title */
			$output .= $this->_show_open_list( $echo );
			$output .= $this->_show_list( $echo, $employees );
			$output .= $this->_show_close_list( $echo );
			
			if( $echo )
				echo $output;
			else
				return $output;
		}
		
		/**
		 * Build the output for the title area of the list
		 * @param $echo whether or not to echo the output
		 *
		 * @uses active_directory_employee_list::$before_list
		 * @uses active_directory_employee_list::$title
		 * @uses active_directory_employee_list::$title_wrap - defaults to span if empty
		 * @uses active_directory_employee_list::$title_class
		 * @uses active_directory_employee_list::$title_id
		 * @uses active_directory_employee_list::$after_title
		 */
		function _show_title( $echo=false ) {
			$output = '';
			if( !empty( $this->before_list ) ) {
				$output .= $this->before_list;
				if( true === $echo ) {
					echo $output;
					$output = '';
				}
			}
			if( !empty( $this->title ) ) {
				$output .= '<' . ( empty( $this->title_wrap ) ? 'span' : $this->title_wrap ) . ( empty( $this->title_class ) ? '' : ' class="' . $this->title_class . '"' ) . ( empty( $this->title_id ) ? '' : ' id="' . $this->title_id . '"' ) . '>';
				$output .= $this->title;
				$output .= '</' . ( empty( $this->title_wrap ) ? 'span' : $this->title_wrap ) . '>';
				if( true === $echo ) {
					echo $output;
					$output = '';
				}
			}
			if( !empty( $this->after_title ) ) {
				$output .= $this->after_title;
				if( true === $echo ) {
					echo $output;
					$output = '';
				}
			}
			
			$output .= $this->simple_search_form();
			
			return $output;
		}
		
		/**
		 * Open the list of employees
		 * @param bool $echo whether or not to echo the output
		 * 
		 * @uses active_directory_employee_list::$list_wrap - if empty, nothing is returned
		 * @uses active_directory_employee_list::$list_class
		 * @uses active_directory_employee_list::$list_id
		 */
		function _show_open_list( $echo=false ) {
			if( empty( $this->list_wrap ) )
				return '';
			$output = '<' . $this->list_wrap . ( empty( $this->list_class ) ? '' : ' class="' . $this->list_class . '"' ) . ( empty( $this->list_id ) ? '' : ' id="' . $this->list_id . '"' ) . '>';
			if( $echo ) {
				echo $output;
				$output = '';
			}
			
			return $output;
		}
		
		/**
		 * Display the list of employees
		 * @param bool $echo whether or not to echo the output
		 */
		function _show_list( $echo=false, $employees=array() ) {
			if( empty( $employees ) )
				return;
			$output = '';
			
			foreach( $employees as $k=>$e ) {
				if( !empty( $this->item_wrap ) ) {
					$fields = array_map( array( $this, '_map_fields_to_vars' ), array_keys( $employees[$k] ) );
					$repl = array_map( array( $this, 'sanitize_html_id_class' ), $employees[$k] );
					
					$output .= '<' . $this->item_wrap . ( empty( $this->item_class ) ? '' : ' class="' . $this->item_class . '"' ) . ( empty( $this->item_id ) ? '' : ' id="' . str_ireplace( $fields, $repl, $this->item_id ) . '"' ) . '>';
					if( $echo ) {
						echo $output;
						$output = '';
					}
				}
				if( $echo ) {
					echo $e;
				} else {
					$output .= $e;
				}
				if( !empty( $this->item_wrap ) ) {
					$output .= '</' . $this->item_wrap . '>';
					if( $echo ) {
						echo $e;
						$output = '';
					}
				}
			}
			
			return $output;
		}
		
		/**
		 * Display the closing list tag and after_list code
		 */
		function _show_close_list( $echo ) {
			$output = '';
			if( !empty( $this->list_wrap ) ) {
				if( $echo )
					echo '</' . $this->list_wrap . '>';
				else
					$output .= '</' . $this->list_wrap . '>';
			}
			if( !empty( $this->after_list ) ) {
				if( $echo )
					echo $this->after_list;
				else
					$output .= $this->after_list;
			}
			
			return $output;
		}
		
		/**
		 * Turns the input variable into a template-friendly variable
		 * @param string $v the string to convert
		 * @return string the converted string
		 */
		function _map_fields_to_vars( $v ) {
			if( empty( $v ) )
				return;
			
			return '%' . strtolower( $v ) . '%';
		}
		
		/**
		 * Attempts to sanitize the input variable to be used as an HTML ID or class
		 * @param string $v the input string to be sanitized
		 * @return string the sanitized string
		 */
		function sanitize_html_id_class( $v ) {
			if( empty( $v ) )
				return;
			if( is_numeric( $v[0] ) )
				$v = 'class-' . $v;
			return strtolower( preg_replace( '/[^a-zA-Z0-9\-\_]/s', '-', $v ) );
		}
		
		/**
		 * Retrieve the list of employees from active directory
		 * @return array the retrieved list
		 * 
		 * @uses active_directory_employee_list::$fields_to_show
		 * @uses active_directory_employee_list::$ad_group
		 * @uses active_directory_employee_list::$employee_list
		 * @uses active_directory_employee_list::map_group_members()
		 */
		function get_employees() {
			if( isset( $this->employee_list ) )
				return $this->employee_list;
			
			$fields_to_show = $this->fields_to_show;
			if( !in_array( 'samaccountname', $fields_to_show ) )
				array_unshift( $fields_to_show, 'samaccountname' );
			
			$hashkey = 'adel_group_' . md5( $this->ad_group . $fields_to_show );
			$tmp = get_transient( $hashkey );
			if( false !== $tmp )
				return $this->employee_list = $tmp;
			
			$this->_log( "\n<!-- AD Group: \n", $this->ad_group, "\n Fields to show: \n", $fields_to_show, "\n-->\n" );
			
			$this->open_ldap();
			
			if( !empty( $this->ad_group ) ) {
				$e = $this->ldap->get_group_users_info( $this->ad_group, $fields_to_show );
			} else {
				$e = $this->ldap->get_group_users_info( null, $fields_to_show );
			}
			$this->employee_list = $this->map_group_members( $e );
			set_transient( $hashkey, $this->employee_list, $this->transient_timeout );
			return $this->employee_list;
		}
		
		/**
		 * Retrieve a single employee by username
		 * @param string $username the user's samaccountname or userPrincipalName
		 * @param array $fields the fields to retrieve
		 */
		function get_employee( $username=null, $fields=array() ) {
			if( empty( $username ) )
				return;
			
			$this->_get_options();
			
			if( empty( $this->output_builder ) )
				$this->_get_default_output();
			
			if( !empty( $fields ) && is_array( $fields ) )
				$this->fields_to_show = $fields;
			$fields_to_show = $this->fields_to_show;
			
			$hashkey = 'adel_user_' . md5( $username . $fields_to_show );
			$tmp = get_transient( $hashkey );
			if( false !== $tmp )
				return $tmp;
			
			$this->open_ldap();
			
			if( !in_array( 'samaccountname', $fields_to_show ) )
				array_unshift( $fields_to_show, 'samaccountname' );
			
			$this->_log( "\n<!-- The username is: $username and the fields array looks like: \n", $fields_to_show, "\n-->\n" );
			
			$e = $this->ldap->user_info( $username, $fields_to_show );
			$e = $this->map_group_members( $e );
			
			set_transient( $hashkey, $e, $this->transient_timeout );
			
			/*print( "\n<!-- The user array looks like: \n" );
			var_dump( $e );
			print( "\n-->\n" );*/
			
			return $e;
		}
		
		/**
		 * Display a single employee
		 * @param string $username the user's samaccountname or userPrincipalName
		 * @param array $fields the list of fields to retrieve
		 * @param bool $echo whether or not to echo the output
		 * @return mixed if $echo is true, the output of the print cmd, else the output
		 * 
		 * @uses active_directory_employee_list_output::get_employee()
		 * @uses active_directory_employee_list_output::_parse_template()
		 * @uses active_directory_employee_list_output::_replace_tags()
		 */
		function show_employee( $username=null, $fields=array(), $formatting=array(), $echo=false ) {
			$this->open_ldap();
			
			$e = array_pop( $this->get_employee( $username, $fields ) );
			
			$this->_extract_formats( $formatting );
			
			$this->_parse_template();
			
			$this->_log( "\n<!-- The list of replacement tags looks like: \n", $this->_replacement_tags, "\n --> \n" );
			
			$e = $this->_replace_tags( $e, $username );
			
			$this->_log( "\n<!-- The employee looks like: \n", $e, "\n --> \n" );
			
			return $echo ? print( $e ) : $e;
		}
		
		/**
		 * Map any information returned by the AD search function to a usable array
		 */
		protected function map_group_members( $users=array() ) {
			/*print( "\n<!-- The raw users array looks like:\n" );
			var_dump( $users );
			print( "\n-->\n" );*/
			
			$i = 0;
			
			$us = array();
			foreach( $users as $user ) {
				if( is_array( $user ) ) {
					if( array_key_exists( 'samaccountname', $user ) ) {
						$sn = $user['samaccountname'][0];
					} else {
						$sn = count( $us );
					}
					$us[$sn] = array();
					$us[$sn]['samaccountname'] = $sn;
					foreach( $this->fields_to_show as $field ) {
						$us[$sn][$field] = array_key_exists( $field, $user ) ? $user[$field][0] : null;
					}
					$i++;
				}
			}
			return $us;
		}
		
		/**
		 * Extract any override formatting options and set the appropriate properties
		 * @param array $formatting
		 */
		function _extract_formats( $formatting=array() ) {
			if( !is_array( $formatting ) || empty( $formatting ) )
				return;
			
			$format_opts = $this->_get_format_option_list();
			foreach( $formatting as $p=>$v ) {
				if( property_exists( $this, $p ) && in_array( $p, $format_opts ) )
					$this->$p = $v;
			}
		}
		
		/**
		 * Sets a simple default value for the active_directory_employee_list::$output_builder property
		 */
		function _get_default_output() {
			$this->output_builder = '<ul><li>%' . implode( '%</li><li>%', $this->fields_to_show ) . '%</li></ul>';
			return $this->output_builder;
		}
		
		/**
		 * Attempt to identify and replace any template tags with their content equivalents
		 */
		function _parse_template( $content=null ) {
			if( !empty( $this->output_built ) && $content == $this->output_builder )
				return $this->output_built;
			
			$content = empty( $content ) ? $this->output_builder : $content;
			
			$pattern = $this->get_template_regexp();
			$content = preg_replace_callback('/'.$pattern.'/s', array( $this, 'do_template_tag' ), $content);
			$this->output_built = $content;
			return $this->output_built;
		}
		
		/**
		 * Evaluate and parses the template for an individual item
		 * Evaluates the active_directory_employee_list_output::$output_built property, handles any
		 * 		conditional statements and replaces any of the variable placeholders with content.
		 * @param array $user the array of information retrieved from AD for this user
		 * @param string $username an optional, deprecated parameter for the user's samaccountname
		 * @return the complete, parsed content for the list item
		 *
		 * @uses active_directory_employee_list_output::$_replacement_tags
		 * @uses active_directory_employee_list_output::_handle_if_else()
		 */
		function _replace_tags( $user, $username=null ) {
			$tags = array();
			foreach( $this->_replacement_tags as $t ) {
				if( array_key_exists( strtolower( $t ), $user ) ) {
					$tags[] = $user[$t];
				} else {
					$tags[] = '';
				}
			}
			$content = $this->_handle_if_else( $this->output_built, $user );
			return vsprintf( $content, $tags );
		}
		
		/**
		 * Evaluates and parses any conditional statements in the output
		 * @param string $content the content to be parsed. If null, active_directory_employee_list_output::$output_built 
		 * 		will be used instead.
		 * @param array $user the array containing all of the information retrieved from AD
		 * @return string the parsed content
		 */
		function _handle_if_else( $content=null, $user ) {
			if( empty( $content ) )
				$content = $this->output_built;
			
			if( !stristr( $content, '[if' ) )
				return $content;
			
			$pattern = '\[if(.+)\](.+)\[endif\]';
			preg_match_all( '/' . $pattern . '/sU', $content, $gmatches, PREG_SET_ORDER );
			
			$this->_log( "\n<!-- Global matches look like: \n", $gmatches, "\n-->\n" );
			
			foreach( $gmatches as $match ) {
				$pattern = '\[(if|elseif|else)(.*?)\]([^\[]+)';
				preg_match_all( '/' . $pattern . '/s', $match[0], $matches, PREG_SET_ORDER );
				
				$replacement = false;
				foreach( $matches as $k=>$m ) {
					$condition = trim( $m[2] );
					if( $replacement )
						continue;
					
					if( !empty( $condition ) ) {
						if( array_key_exists( $condition, $user ) && !empty( $user[$condition] ) ) {
							$replacement = $m[3];
						}
					} elseif( 'else' == $m[1] ) {
						$replacement = $m[3];
					}
				}
				
				$content = str_replace( $match[0], $replacement, $content );
			}
			
			return $content;
		}
		
		/**
		 * Perform the replacement of a specific template tag
		 */
		function do_template_tag( $m ) {
			$template_tags = $this->get_template_tags();
			
			$this->_log( "\n<!-- Matches look like: \n", $m, "\n-->\n" );
			
			$this->_replacement_tags[] = $m[2];
			return $m[1] . '%' . count( $this->_replacement_tags ) . '$s' . $m[3];
		}
		
		/**
		 * Return a regexp pattern for the template tags
		 */
		function get_template_regexp() {
			if( !empty( $this->_template_regexp ) )
				return $this->_template_regexp;
			
			$template_tags = $this->get_template_tags();
			
			$this->_log( "\n<!-- Template tags array looks like: \n", $template_tags, "\n -->\n" );
			
			$tagregexp = join( '|', array_map( 'preg_quote', $template_tags ) );
			$this->_template_regexp = '(.?)\%(' . $tagregexp . ')\%(.?)';
			
			$this->_log( "\n<!-- Template RegExp looks like: \n", $this->_template_regexp, "\n-->\n" );
			
			return $this->_template_regexp;
		}
		
		/**
		 * Get a list of the allowed template tags
		 */
		function get_template_tags( $keys=true ) {
			/**
			 * Descriptions/list of AD/LDAP fields gleened from 
			 * 		http://www.computerperformance.co.uk/Logon/LDAP_attributes_active_directory.htm
			 */
			$tags = array( 
				'cn' 				=> 'Common name - First name and last name together',
				'description' 		=> 'Full text description of user/group',
				'displayname'		=> 'The name that should be displayed as the user\'s name',
				'dn'				=> 'The pre-formatted user string used to bind to active directory',
				'givenname'			=> 'The user\'s first name',
				'name'				=> 'Should be the same as CN',
				'samaccountname'	=> 'The unique user ID of the user (generally the login name)',
				'sn'				=> 'The user\'s last name',
				'userprincipalname'	=> 'A unique user ID, complete with domain, used for logging in',
				'mail'				=> 'The user\'s email address',
				'mailnickname'		=> 'The username portion of the user\'s email address',
				'c'					=> 'Country or region',
				'company'			=> 'The name of the user\'s company',
				'department'		=> 'The name of the user\'s department in the company',
				'homephone'			=> 'The user\'s home telephone number',
				'l'					=> 'The physical location (city) of the user',
				'location'			=> 'The computer location (??) of the user?',
				'manager'			=> 'The user\'s boss or manager',
				'mobile'			=> 'The user\'s mobile phone number',
				'ou'				=> 'Organizational unit',
				'postalcode'		=> 'ZIP code',
				'st'				=> 'State, province or county',
				'streetaddress'		=> 'First line of postal address',
				'telephonenumber'	=> 'Office phone number',
			);
			return $keys ? array_keys( $tags ) : $tags;
		}
		
		/**
		 * Register the ad-employee-list shortcode
		 */
		function add_shortcode() {
			add_shortcode( 'ad-employee-list', array( &$this, 'render_shortcode' ) );
		}
		
		/**
		 * Render the ad-employee-list shortcode
		 * @param array $atts the list of attributes (if any)
		 * @return string the rendered code
		 */
		function render_shortcode( $atts ) {
			$content = '';
			
			extract( shortcode_atts( array( 'fields' => array(), 'group' => null, 'username' => null ), $atts ) );
			$atts = shortcode_atts( array_fill_keys( $this->_get_format_option_list(), null ), $atts );
			
			$this->_log( "\n<!-- The fields option looks like: \n", $fields, "\n and the group option looks like: \n", $group, "\n while the atts param looks like: \n", $atts, "\n -->\n" );
			
			if( !is_array( $fields ) )
				$fields = explode( ',', str_replace( ' ', '', $fields ) );
			if( !is_array( $fields ) )
				$fields = array( $fields );
			
			$formatting = array();
			foreach( $this->_get_format_option_list() as $opt ) {
				if( !is_null( $atts[$opt] ) )
					$formatting[$opt] = $atts[$opt];
			}
			
			$content .= !is_null( $username ) ? 
				$this->show_employee( $username, $fields, $formatting, false ) : 
				$this->show_employees( $group, $fields, $formatting, false );
			
			return $content;
		}
		
		/**
		 * Retrieve a list of all of the output option names
		 */
		function _get_format_option_list() {
			return array( 'before_list', 'title_wrap', 'title_class', 'after_title', 'list_wrap', 'list_class', 'item_wrap', 'item_class', 'after_list', 'title_id', 'list_id', 'item_id', 'title', 'output_builder' );
		}
		
		/**
		 * Retrieve and format search results
		 * @param string|array $keyword the keyword(s) to search for
		 * @param string|array $field the field(s) to search
		 * If an array is passed to both parameters, they will be treated as corresponding arrays.
		 * 		The first item in the $keyword array will be searched for in the field indicated 
		 * 		as the first item in the $field array, and so on.
		 * @return array the list of employees that matched the search query
		 */
		function search_employees( $keyword, $field=null, $group=null ) {
			$this->open_ldap();
			
			if( empty( $field ) ) {
				$field = $this->fields_to_show;
			}
			if( !is_array( $field ) )
				$field = explode( ',', $field );
			if( !is_array( $field ) )
				$field = array( $field );
			if( !in_array( 'samaccountname', $field ) )
				array_unshift( $field, 'samaccountname' );
			if( !is_array( $keyword ) )
				$keyword = array_fill( 0, count( $field ), $keyword );
			
			$fields_to_show = $this->fields_to_show;
			if( !in_array( 'samaccountname', $fields_to_show ) )
				array_unshift( $fields_to_show, 'samaccountname' );
			
			$hashkey = 'adel_search_' . md5( 'k=' . ( is_array( $keyword ) ? implode( '|', $keyword ) : $keyword ) . 'f=' . ( is_array( $field ) ? implode( '|', $field ) : $field ) . 'fs=' . ( is_array( $fields_to_show ) ? implode( '|', $fields_to_show ) : $fields_to_show ) . ( !empty($group) ? 'g=' . $group : '' ) );
			$e = get_transient( $hashkey );
			if( false !== $e )
				return $e;
			
			print( "\n<!-- Preparing to search the following fields: \n" );
			var_dump( $field );
			print( "\n for the following values: \n" );
			var_dump( $keyword );
			print( "\n -->\n" );
			
			$e = $this->map_group_members( $this->ldap->search_users( $field, $keyword, $fields_to_show, $group ) );
			set_transient( $hashkey, $e, $this->transient_timeout );
			
			return $e;
		}
		
		/**
		 * Build an advanced search form
		 * Allow users to search each individual field that's being retrieved
		 */
		function advanced_search_form( $fields=null, $echo=false ) {
			if( is_null( $fields ) ) {
				if( empty( $this->fields_to_show ) )
					$this->_get_options();
				$fields = $this->fields_to_show;
			}
			$form = '';
			$form .= '
	<form>
		<p>
			<label for="adeq" class="adel-search-label">' . __( 'Search employees: ', $this->text_domain ) . '</label> 
			<input class="adel-search-query" type="text" name="adeq" id="adeq" placeholder="Search the list of employees" value="' . (isset($_REQUEST['adeq'])?$_REQUEST['adeq']:'') . '"/> 
		</p>';
			
			foreach( $fields as $f ) {
				$form .= '
		<p>' . $this->build_search_field( $f ) . '
		</p>';
			}
			
			$form .= '
		<p>
			<input class="adel-search-submit" type="submit" value="' . __( 'Search', $this->text_domain ) . '"/>
		</p>
	</form>';
		}
		
		/**
		 * Build a simple search form
		 */
		function simple_search_form( $echo=false ) {
			$form = '';
			$form .= '
	<form>
		<p>
			<label for="adeq" class="adel-search-label">' . __( 'Search employees: ', $this->text_domain ) . '</label> 
			<input class="adel-search-query" type="text" name="adeq" id="adeq" placeholder="Search the list of employees" value="' . (isset($_REQUEST['adeq'])?$_REQUEST['adeq']:'') . '"/> 
			<input class="adel-search-submit" type="submit" value="' . __( 'Search', $this->text_domain ) . '"/>
		</p>
	</form>';
			return $echo ? print( $form ) : $form;
		}
	}
}