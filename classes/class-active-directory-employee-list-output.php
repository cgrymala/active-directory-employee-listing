<?php
/**
 * Output and formatting class and method definitions for the active-directory-employee-list plugin
 * @package Active-Directory-Employee-List
 * @subpackage FrontEnd
 * @version 0.3
 */
if( !class_exists( 'active_directory_employee_list' ) )
	require_once( 'class-active-directory-employee-list.php' );

if( !class_exists( 'active_directory_employee_list_output' ) ) {
	/**
	 * Class definition for output and formatting for the active directory employee list plugin
	 */
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
		/**
		 * A switch to determine whether we've filtered this content already
		 */
		var $already_filtered 			= false;
		
		function _init() {
			parent::_init();
			$this->add_shortcode();
			add_filter( 'the_content', array( &$this, 'display_search_results' ), 1 );
			add_action( 'wp_print_styles', array( &$this, 'print_styles' ), 1 );
		}
		
		/**
		 * Enqueue the stylesheet
		 */
		function print_styles() {
			wp_enqueue_style( 'active-directory-employee-list', plugins_url( '/css/active-directory-employee-list.css', dirname(__FILE__) ), array(), '0.3.1', 'all' );
		}
		
		/**
		 * Replace the content of the current page with the search results from this plugin
		 */
		function display_search_results( $content ) {
			if( !isset( $_REQUEST['adeq'] ) || $this->already_filtered || stristr( $content, 'class="adel-list"' ) )
				return $content;
			
			$this->already_filtered = true;
			remove_filter( 'the_content', 'wpautop' );
			remove_filter( 'the_content', array( &$this, 'display_search_results' ), 1 );
			return $content . $this->show_employees( null, array(), array(), false, true, true, false );
		}
		
		/**
		 * Actually output the employee list
		 * @param string|array $group the name of the AD group to retrieve (null means all people will be retrieved)
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
			/*$this->_log( "\n<!-- Built output looks like: \n", $this->output_built, "\n and output builder looks like: \n", $this->output_builder, "\n-->\n" );*/
			
			if( !is_null( $group ) )
				$this->ad_group = $group;
			if( !empty( $fields ) )
				$this->fields_to_show = $fields;
			
			if( !is_array( $this->fields_to_show ) )
				$this->fields_to_show = array_map( 'trim', explode( ';', $this->fields_to_show ) );
			
			if( in_array( 'gravatar', $this->fields_to_show ) ) {
				unset( $this->fields_to_show[array_search( 'gravatar', $this->fields_to_show )] );
				if( !in_array( 'mail', $this->fields_to_show ) )
					array_push( $this->fields_to_show, 'mail' );
				if( !in_array( 'targetaddress', $this->fields_to_show ) )
					array_push( $this->fields_to_show, 'targetaddress' );
			}
			
			if( !empty( $this->ad_group ) && !is_array( $this->ad_group ) )
				$this->ad_group = array_map( 'trim', explode( ';', $this->ad_group ) );
			
			$output = array();
			
			/*error_log( '[ADEL Debug]: We are about to query for the list of employees' );*/
			
			if( isset( $_REQUEST['adeq'] ) && !empty( $_REQUEST['adeq'] ) ) {
				$this->_log( $group, $_REQUEST['adeq'], print_r( $group, true ) );
				$employees = $this->search_employees( $_REQUEST['adeq'], $this->fields_to_show, $this->ad_group );
				/*error_log( '[ADEL Debug]: We have retrieved a list of employees by performing a search' );*/
			} else {
				$employees = $this->get_employees();
				/*error_log( '[ADEL Debug]: We have retrieved a list of employees by using the get_employees() function' );*/
			}
			if( empty( $employees ) || !is_array( $employees ) )
				return array( 'noresults' => apply_filters( 'adel-no-results-text', __( 'No employees could be found matching the criteria specified. Please try a different search. If you searched for a person\'s first name and last name together, please try searching for just the first or last name.', $this->text_domain ) ) );
			if( !empty( $this->order_by ) )
				$this->_sort_by_val( $employees, $this->order_by );
			
			/*foreach( $employees as $username=>$e ) {
				if( $this->_ignore_this_user( $e, $username ) )
					continue;
				$output[$username] = $this->_replace_tags( $e, $username );
			}*/
			
			return $employees;
		}
		
		/**
		 * Determine whether or not to ignore this user, based on the values of the always_ignore var
		 */
		protected function _ignore_this_user( $e=array(), $username=null ) {
			return false;
			
			if( empty( $e ) )
				return true;
			if( empty( $this->always_ignore ) )
				return false;
			
			foreach( $this->always_ignore as $c ) {
				if( empty( $c ) )
					continue;
				
				list( $key, $val ) = explode( '=', $c );
				$val = str_replace( array( '"', "'" ), '', $val );
				if( '!' == $key[0] ) {
					$key = substr( $key, 1 );
					if( $e[$key] != $val )
						return true;
				} else {
					if( $e[$key] == $val )
						return true;
				}
			}
			return false;
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
		function show_employees( $group=null, $fields=array(), $formatting=array(), $echo=true, $show_title=true, $wrap_list=true, $include_search=true ) {
			if( !empty( $group ) && !is_array( $group ) )
				$group = array_map( 'trim', explode( ';', $group ) );
			
			/*error_log( '[ADEL Notice]: Preparing to retrieve info about ' . print_r( $group, true ) . '. The fields we are retrieving are: ' . print_r( $fields, true ) );*/
			$employees = $this->list_employees( $group, $fields, $formatting );
			
			$output = '';
			if( $show_title )
				$output .= $this->_show_title( $echo, $include_search ); /* before_list, title_wrap, title, after_title */
			if( $wrap_list )
				$output .= $this->_show_open_list( $echo );
			$output .= $this->_show_list( $echo, $employees );
			if( $wrap_list )
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
		function _show_title( $echo=false, $include_search=true ) {
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
			
			if( $include_search )
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
			
			if( isset( $_REQUEST['adep'] ) && !empty( $_REQUEST['adep'] ) )
				$page = $_REQUEST['adep'];
			else
				$page = 1;
			
			$total = count( $employees );
			if( 0 == $total ) {
				return $output;
			}
			
			/*error_log( '[ADEL Debug]: We should be displaying ' . $this->results_per_page . ' entries on each page.' );*/
			
			if( 0 <= $this->results_per_page )
				$employees = array_slice( $employees, ( ( $page - 1 ) * $this->results_per_page ), $this->results_per_page, true );
			else
				$this->results_per_page = $total;
			
			foreach( $employees as $k=>$e ) {
				$e = $this->_replace_tags( $e, $e['samaccountname'] );
				if( !empty( $this->item_wrap ) && is_array( $employees[$k] ) ) {
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
			
			if( $total > $this->results_per_page ) {
				/* We need to show pagination links */
				if( 1 < $page ) {
					/* We need to show the "previous page" link */
					$output .= str_replace( '%link%', '?adep=' . ( $page + 1 ), $this->prev_page_link );
				}
				if( ( $page * $this->results_per_page ) < $total )
					/* We need to show the "next page" link */
					$output .= str_replace( '%link%', '?adep=' . ( $page + 1 ), $this->next_page_link );
			}
			if( $echo ) {
				echo $e;
				$output = '';
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
			/*error_log( '[ADEL Debug]: We have entered the get_employees() function' );*/
			if( isset( $this->employee_list ) )
				return $this->employee_list;
			
			/*error_log( '[ADEL Debug]: The list of employees was not pre-populated, so we are building it' );*/
			if( !empty( $this->ad_group ) && !is_array( $this->ad_group ) )
				$this->ad_group = array_map( 'trim', explode( ';', $this->ad_group ) );
			
			$fields_to_show = $this->fields_to_show;
			
			if( !is_array( $fields_to_show ) )
				$fields_to_show = array_map( 'trim', explode( ';', $fields_to_show ) );
			
			if( !in_array( 'samaccountname', $fields_to_show ) )
				array_unshift( $fields_to_show, 'samaccountname' );
			if( !empty( $this->order_by ) && !in_array( $this->order_by, $fields_to_show ) )
				array_push( $fields_to_show, $this->order_by );
			
			$hashkey = 'adel_group_' . md5( implode( ';', $this->ad_group ) . $fields_to_show );
			if( 0 === $this->transient_timeout )
				delete_transient( $hashkey );
			
			$tmp = get_transient( $hashkey );
			if( false !== $tmp )
				return $this->employee_list = $tmp;
			
			$this->_log( "<!-- AD Group: ", $this->ad_group, " Fields to show: ", $fields_to_show, "-->" );
			
			if( true !== ( $e = $this->open_ldap() ) )
				wp_die( 'The following error occurred: <pre><code>' . $e . '</code></pre>' );
			
			if( !empty( $this->ad_group ) ) {
				$e = array();
				foreach( $this->ad_group as $g ) {
					/*error_log( '[ADEL Debug]: We are in the get_employees() function preparing to retrieve users in the ' . $g . ' group.' );*/
					$d = $this->ldap->get_group_users_info( $g, $fields_to_show );
					/*error_log( '[ADEL Debug]: We successfully retrieved the list of users in this group, and are preparing to merge that with the users we have retrieved from other groups.' );*/
					$e = array_merge( $e, $d );
				}
			} else {
				$e = $this->ldap->get_group_users_info( null, $fields_to_show );
			}
			/*error_log( '[ADEL Debug]: We are preparing to map group members' );*/
			$this->employee_list = $this->map_group_members( $e );
			delete_transient( $hashkey );
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
			if( 0 === $this->transient_timeout )
				delete_transient( $hashkey );
			
			$tmp = get_transient( $hashkey );
			if( false !== $tmp )
				return $tmp;
			
			if( true !== ( $e = $this->open_ldap() ) )
				wp_die( 'The following error occurred: <pre><code>' . $e . '</code></pre>' );
			
			if( !in_array( 'samaccountname', $fields_to_show ) )
				array_unshift( $fields_to_show, 'samaccountname' );
			
			/*$this->_log( "\n<!-- The username is: $username and the fields array looks like: \n", $fields_to_show, "\n-->\n" );*/
			
			$e = $this->ldap->user_info( $username, $fields_to_show );
			if( empty( $e ) && !empty( $this->_account_suffix ) )
				$e = $this->ldap->user_info( $username . $this->_account_suffix, $fields_to_show );
			
			$e = $this->map_group_members( $e );
			
			delete_transient( $hashkey );
			set_transient( $hashkey, $e, $this->transient_timeout );
			
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
			if( true !== ( $e = $this->open_ldap() ) )
				wp_die( 'The following error occurred: <pre><code>' . $e . '</code></pre>' );
			
			/*error_log( '[ADEL Notice]: Preparing to retrieve info about ' . $username . '. The fields we are retrieving are: ' . print_r( $fields, true ) );*/
			$e = array_pop( $this->get_employee( $username, $fields ) );
			
			$this->_extract_formats( $formatting );
			
			$this->_parse_template();
			
			/*$this->_log( "\n<!-- The list of replacement tags looks like: \n", $this->_replacement_tags, "\n --> \n" );*/
			
			$e = $this->_replace_tags( $e, $username );
			
			/*$this->_log( "\n<!-- The employee looks like: \n", $e, "\n --> \n" );*/
			
			return $echo ? print( $e ) : $e;
		}
		
		/**
		 * Map any information returned by the AD search function to a usable array
		 */
		protected function map_group_members( $users=array(), $groupname=null ) {
			$i = 0;
			
			$us = array();
			if( empty( $users ) )
				$users = array();
			
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
					if( !empty( $groupname ) )
						$us[$sn]['activeusergroup'] = $groupname;
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
			if( !is_array( $this->fields_to_show ) )
				return null;
			
			$this->output_builder = '<ul>';
			foreach( $this->fields_to_show as $f ) {
				$this->output_builder .= '<li class="' . $f . '">%' . $f . '%</li>';
			}
			$this->output_builder .= '</ul>';
			/*$this->output_builder = '<ul><li>%' . implode( '%</li><li>%', $this->fields_to_show ) . '%</li></ul>';*/
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
			if( empty( $user ) || !is_array( $user ) )
				return $user;
			$tags = array();
			foreach( $this->_replacement_tags as $t ) {
				if( 'gravatar' == $t ) {
					$m = array_key_exists( 'targetaddress', $user ) && !empty( $user['targetaddress'] ) ? $user['targetaddress'] : $user['mail'];
					/*$tags[] = 'http://www.gravatar.com/avatar/' . md5( trim( strtolower( $m ) ) ) . '?d=identicon&size=' . $this->gravatar_size;*/
					$tags[] = get_avatar( strtolower( $m ), $this->gravatar_size );
				} elseif( array_key_exists( strtolower( $t ), $user ) ) {
					$tags[] = $user[$t];
				} else {
					$tags[] = '';
				}
			}
			
			/*error_log( '[ADEL Debug]: The replacement tags array looks like: ' . print_r( $this->_replacement_tags, true ) );
			error_log( '[ADEL Debug]: The tag values array looks like ' . print_r( $tags, true ) );*/
			
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
			
			$pattern = '\[if([^\]]+)\](.+)\[\/*end\s*if\]';
			preg_match_all( '/' . $pattern . '/sU', $content, $gmatches, PREG_SET_ORDER );
			
			foreach( $gmatches as $match ) {
				$pattern = '\[(if|elseif|else)(.*?)\]([^\[]+)';
				preg_match_all( '/' . $pattern . '/s', $match[0], $matches, PREG_SET_ORDER );
				
				$replacement = false;
				foreach( $matches as $k=>$m ) {
					$condition = trim( $m[2] );
					if( $replacement )
						continue;
					
					if( !empty( $condition ) ) {
						/* We have to check for inclusive conditions */
						if( strstr( $condition, '&&' ) ) {
							if( $this->_handle_if_else_inclusive( $condition, $user ) )
								$replacement = $m[3];
						/* We have to check for exclusive conditions */
						} elseif( strstr( $condition, '||' ) ) {
							if( $this->_handle_if_else_exclusive( $condition, $user ) )
								$replacement = $m[3];
						/* We have to check for single condition */
						} else {
							if( $this->_handle_if_else_single( $condition, $user ) )
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
		 * Handle inclusive (and) conditions in an if/else statement
		 */
		protected function _handle_if_else_inclusive( $condition=null, $user=array() ) {
			$check = explode( '&&', $condition );
			$passed = false;
			foreach( $check as $c ) {
				if( $this->_handle_if_else_single( $c, $user ) )
					$passed = true;
				else
					return false;
			}
			return $passed;
		}
		
		/**
		 * Handle exclusive (or) conditions in an if/else statement
		 */
		protected function _handle_if_else_exclusive( $condition=null, $user=array() ) {
			$check = explode( '||', $condition );
			$passed = false;
			foreach( $check as $c ) {
				if( $this->_handle_if_else_single( $c, $user ) )
					return true;
			}
			return false;
		}
		
		/**
		 * Handle a single condition in an if/else statement
		 */
		protected function _handle_if_else_single( $c=null, $user=array() ) {
			if( empty( $user ) || !is_array( $user ) )
				return false;
			
			$c = trim( $c );
			if( strstr( $c, '=' ) ) {
				list( $key, $val ) = explode( '=', $c );
				$key = trim( $key );
				$val = trim( str_replace( array( '"', "'" ), '', $val ) );
				if( '!' == $key[0] ) {
					$key = substr( $key, 1 );
					if( !array_key_exists( $key, $user ) || $user[$key] != $val ) {
						return true;
					}
				} elseif( array_key_exists( $key, $user ) && $user[$key] == $val ) {
					return true;
				}
			} else {
				if( '!' == $c[0] ) {
					$key = substr( $c, 1 );
					if( !array_key_exists( $key, $user ) || empty( $user[$key] ) ) {
						return true;
					}
				} elseif( array_key_exists( $c, $user ) && !empty( $user[$c] ) ) {
					return true;
				}
			}
			return false;
		}
		
		/**
		 * Perform the replacement of a specific template tag
		 */
		function do_template_tag( $m ) {
			$template_tags = $this->get_template_tags();
			
			/*$this->_log( "\n<!-- Matches look like: \n", $m, "\n-->\n" );*/
			
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
			
			/*$this->_log( "\n<!-- Template tags array looks like: \n", $template_tags, "\n -->\n" );*/
			
			$tagregexp = join( '|', array_map( 'preg_quote', $template_tags ) );
			$this->_template_regexp = '(.?)\%(' . $tagregexp . ')\%(.?)';
			
			/*$this->_log( "\n<!-- Template RegExp looks like: \n", $this->_template_regexp, "\n-->\n" );*/
			
			return $this->_template_regexp;
		}
		
		/**
		 * Register the ad-employee-list shortcode
		 */
		function add_shortcode() {
			add_shortcode( 'ad-employee-list', array( &$this, 'render_shortcode' ) );
			add_shortcode( 'ad-employee-simple-search', array( &$this, 'simple_search_form' ) );
			add_shortcode( 'ad-employee-advanced-search', array( &$this, 'advanced_search_form' ) );
			add_shortcode( 'ad-employee-custom-search', array( &$this, 'custom_search_form' ) );
		}
		
		/**
		 * Render the ad-employee-list shortcode
		 * @param array $atts the list of attributes (if any)
		 * @return string the rendered code
		 */
		function render_shortcode( $atts ) {
			$this->_get_options();
			
			$content = '';
			
			/*error_log( '[ADEL Debug]: The atts array currently looks like: ' . print_r( $atts, true ) );*/
			
			$args = shortcode_atts( array( 'fields' => array(), 'group' => null, 'username' => null, 'include_search' => true, 'results_per_page' => null ), $atts );
			/*error_log( '[ADEL Debug]: The parsed shortcode atts look like: ' . print_r( $args, true ) );*/
			extract( $args );
			
			$atts = shortcode_atts( array_fill_keys( $this->_get_format_option_list(), null ), $atts );
			
			if( !is_array( $fields ) )
				$fields = explode( ',', str_replace( ' ', '', $fields ) );
			if( !is_array( $fields ) )
				$fields = array( $fields );
			if( is_null( $group ) )
				$group = $this->ad_group;
			if( !empty( $group ) && !is_array( $group ) )
				$group = array_map( 'trim', explode( ';', $group ) );
			if( !is_null( $results_per_page ) && is_numeric( $results_per_page ) ) {
				/*error_log( '[ADEL Debug]: We are setting the results_per_page property to ' . $results_per_page );*/
				$this->results_per_page = $results_per_page;
			}
			
			$formatting = array();
			foreach( $this->_get_format_option_list() as $opt ) {
				if( !is_null( $atts[$opt] ) )
					$formatting[$opt] = $atts[$opt];
			}
			
			$content .= !is_null( $username ) ? 
				$this->show_employee( $username, $fields, $formatting, false ) : 
				$this->show_employees( $group, $fields, $formatting, false, $include_search );
			
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
			if( true !== ( $e = $this->open_ldap() ) )
				wp_die( 'The following error occurred: <pre><code>' . $e . '</code></pre>' );
			
			if( !empty( $group ) && !is_array( $group ) )
				$group = array_map( 'trim', explode( ';', $group ) );
			if( empty( $group ) )
				$group = array();
			$group = array_filter( $group );
			if( empty( $group ) )
				$group = $this->ad_group;
			
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
			
			if( !is_array( $this->fields_to_show ) )
				return array();
			
			$fields_to_show = $this->fields_to_show;
			if( !in_array( 'samaccountname', $fields_to_show ) )
				array_unshift( $fields_to_show, 'samaccountname' );
			if( !empty( $this->order_by ) && !in_array( $this->order_by, $fields_to_show ) )
				array_push( $fields_to_show, $this->order_by );
			
			$hashkey = 'adel_search_' . md5( 'k=' . ( is_array( $keyword ) ? implode( '|', $keyword ) : $keyword ) . 'f=' . ( is_array( $field ) ? implode( '|', $field ) : $field ) . 'fs=' . ( is_array( $fields_to_show ) ? implode( '|', $fields_to_show ) : $fields_to_show ) . ( !empty($group) ? 'g=' . implode( ';', $group ) : '' ) );
			if( 0 === $this->transient_timeout )
				delete_transient( $hashkey );
			
			$e = get_transient( $hashkey );
			if( false !== $e ) {
				return $e;
			}
			
			$this->_log( "\n<!-- Preparing to search the following fields: \n", $field, "\n for the following values: \n", $keyword, "\n -->\n" );
			
			$e = array();
			$ai = empty( $this->always_ignore ) ? null : $this->always_ignore;
			foreach( $group as $g ) {
				$d = $this->ldap->search_users( $field, $keyword, $fields_to_show, $g, $ai );
				$d = $this->map_group_members( $d, $g );
				$e = array_merge( $e, $d );
			}
			delete_transient( $hashkey );
			set_transient( $hashkey, $e, $this->transient_timeout );
			
			return $e;
		}
		
		/**
		 * Build an advanced search form
		 * Allow users to search each individual field that's being retrieved
		 */
		function advanced_search_form( $fields=null, $echo=false ) {
			if( empty( $fields ) ) {
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
			
			$form = apply_filters( 'ad-employee-advanced-search', $form );
			
			return $echo ? print( $form ) : $form;
		}
		
		function build_search_field( $f ) {
			if( array_key_exists( $f, $this->_available_fields ) )
				return '<label for="adel-search-criteria-' . $f . '">' . $this->_available_fields[$f] . '</label><input type="text" name="' . $f . '" id="adel-search-criteria-' . $f . '">';
			
			return '';
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
			$form = apply_filters( 'ad-employee-simple-search', $form );
			return $echo ? print( $form ) : $form;
		}
		
		function custom_search_form() {
			$form = apply_filters( 'ad-employee-custom-search', '' );
			return $form;
		}
		
	}
}