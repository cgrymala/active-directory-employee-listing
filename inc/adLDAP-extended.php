<?php
if( !class_exists( 'adLDAP' ) )
	require_once( 'adLDAP.php' );

if( !class_exists( 'adLDAPE' ) ) {
	class adLDAPE extends adLDAP {
		var $_last_query	= null;
		var $_ad_port		= 389;
		/**
		* The account prefix for your domain, can be set when the class is invoked
		* 
		* Added by CAG for the extended version of this class
		* @var string
		*/   
		protected $_account_prefix = "";
		
		/**
		* Set the account prefix
		* 
		* Added by CAG for the extended version of this class
		* @param string $_account_suffix
		* @return void
		*/
		public function set_account_prefix($_account_prefix)
		{
			  $this->_account_prefix = $_account_prefix;
		}
	
		/**
		* Get the account prefix
		* 
		* Added by CAG for the extended version of this class
		* @return string
		*/
		public function get_account_prefix()
		{
			  return $this->_account_prefix;
		}
		
		/**
		* Set the port on which AD listens
		* 
		* Added by CAG for the extended version of this class
		* @param string $_ad_port
		* @return void
		*/
		public function set_ad_port($_ad_port) {
			  $this->_ad_port = intval( $_ad_port );
		}
	
		/**
		* Get the port on which AD listens
		* 
		* Added by CAG for the extended version of this class
		* @return string
		*/
		public function get_ad_port() {
			  return $this->_ad_port;
		}
		
		/**
		* Default Constructor
		* 
		* Tries to bind to the AD domain over LDAP or LDAPs
		* 
		* @param array $options Array of options to pass to the constructor
		* @throws Exception - if unable to bind to Domain Controller
		* @return bool
		*/
		function __construct($options=array()){
			// You can specifically overide any of the default configuration options setup above
			if (count($options)>0){
				if (array_key_exists("account_suffix",$options)){ $this->_account_suffix=$options["account_suffix"]; }
				else { $this->_account_suffix=''; }
				if (array_key_exists("account_prefix",$options)){ $this->_account_prefix=$options["account_prefix"]; }
				if (array_key_exists("base_dn",$options)){ $this->_base_dn=$options["base_dn"]; }
				if (array_key_exists("domain_controllers",$options)){ $this->_domain_controllers=$options["domain_controllers"]; }
				if (array_key_exists("ad_username",$options)){ $this->_ad_username=$options["ad_username"]; }
				if (array_key_exists("ad_password",$options)){ $this->_ad_password=$options["ad_password"]; }
				if (array_key_exists("real_primarygroup",$options)){ $this->_real_primarygroup=$options["real_primarygroup"]; }
				if (array_key_exists("use_ssl",$options)){ $this->_use_ssl=$options["use_ssl"]; }
				if (array_key_exists("use_tls",$options)){ $this->_use_tls=$options["use_tls"]; }
				if (array_key_exists("recursive_groups",$options)){ $this->_recursive_groups=$options["recursive_groups"]; }
				
				/* Added by CAG for the extended version of this class */
				if( is_array( $this->_domain_controllers ) ) {
					foreach( $this->_domain_controllers as $k=>$d ) {
						if( stristr( $d, 'ldaps://' ) ) {
							$this->_use_ssl = true;
							$this->_domain_controllers[$k] = str_ireplace( 'ldaps://', '', $d );
						}
					}
				}
				if (array_key_exists('ad_port',$options)){ $this->set_ad_port($options['ad_port']); }
					elseif($this->_use_ssl) { $this->set_ad_port(636); }
					else { $this->set_ad_port(389); }
				/* End edit */
			}
			
			if ($this->ldap_supported() === false) {
				throw new adLDAPException('No LDAP support for PHP.  See: http://www.php.net/ldap');
			}
	
			return $this->connect();
		}
	
		/**
		* Connects and Binds to the Domain Controller
		* 
		* @return bool
		*/
		public function connect() {
			// Connect to the AD/LDAP server as the username/password
			$dc=$this->random_controller();
			if ($this->_use_ssl){
				/* Port variable added by CAG for extended class. Original version used int 636 */
				$this->_conn = ldap_connect("ldaps://".$dc, $this->_ad_port);
			} else {
				/* Port variable added by CAG for extended class. Original version did not use port at all */
				$this->_conn = ldap_connect($dc, $this->_ad_port);
			}
			
			if( !is_resource( $this->_conn ) ) {
				throw new adLDAPException( 'Connection to Active Directory failed for some reason. The connection is not evaluating as a usable resource. AD said: ' . $this->get_last_error());
				error_log( '[AD LDAP]: The connection is not a resource, so no bind was attempted.' );
				return false;
			}
				   
			// Set some ldap options for talking to AD
			ldap_set_option($this->_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($this->_conn, LDAP_OPT_REFERRALS, 0);
			
			if ($this->_use_tls) {
				ldap_start_tls($this->_conn);
			}
				   
			// Bind as a domain admin if they've set it up
			if ($this->_ad_username!=NULL && $this->_ad_password!=NULL){
				$this->_bind = @ldap_bind($this->_conn,$this->_ad_username.$this->_account_suffix,$this->_ad_password);
				if (!$this->_bind){
					if ($this->_use_ssl && !$this->_use_tls){
						// If you have problems troubleshooting, remove the @ character from the ldap_bind command above to get the actual error message
						throw new adLDAPException('Bind to Active Directory failed. Either the LDAPs connection failed or the login credentials are incorrect. AD said: ' . $this->get_last_error());
						return false;
					} else {
						throw new adLDAPException('Bind to Active Directory failed. Check the login credentials and/or server details. AD said: ' . $this->get_last_error());
						return false;
					}
				}
			}
			
			if ($this->_base_dn == NULL) {
				$this->_base_dn = $this->find_base_dn();   
			}
			
			return (true);
		}
		
		/**
		* Returns a complete list of the groups in AD based on a SAM Account Type  
		* 
		* @param string $samaccounttype The account type to return
		* @param bool $include_desc Whether to return a description
		* @param string $search Search parameters
		* @param bool $sorted Whether to sort the results
		* @return array
		*/
		public function search_groups($samaccounttype = ADLDAP_SECURITY_GLOBAL_GROUP, $include_desc = false, $search = "*", $sorted = true, $acct_name_field = 'samaccountname', $desc_field = 'description') {
			if (!$this->_bind){ return (false); }
			
			$filter = '(&(objectCategory=group)';
			if ($samaccounttype !== null) {
				$filter .= '(samaccounttype='. $samaccounttype .')';
			}
			$filter .= '(cn='.$search.'))';
			// Perform the search and grab all their details
			$fields=array($acct_name_field,$desc_field);
			$sr=ldap_search($this->_conn,$this->_base_dn,$filter,$fields);
			$entries = ldap_get_entries($this->_conn, $sr);
	
			$groups_array = array();        
			for ($i=0; $i<$entries["count"]; $i++){
				if ($include_desc && strlen($entries[$i][$desc_field][0]) > 0 ){
					$groups_array[ $entries[$i][$acct_name_field][0] ] = $entries[$i][$desc_field][0];
				} elseif ($include_desc){
					$groups_array[ $entries[$i][$acct_name_field][0] ] = $entries[$i][$acct_name_field][0];
				} else {
					array_push($groups_array, $entries[$i][$acct_name_field][0]);
				}
			}
			if( $sorted ){ asort($groups_array); }
			return ($groups_array);
		}
		
		public function get_group_users_info( $group=null, $fields, $search='*' ) {
			if( is_null( $fields ) )
				$fields = array( 'samaccountname','mail','department','displayname','telephonenumber' );
			
			$filter = '(&(objectClass=user)(samaccounttype=' . ADLDAP_NORMAL_ACCOUNT . ')(objectCategory=person)' . ( !is_null( $group ) ? '(memberof=cn=' . $group . ',' . $this->_base_dn . ')' : '' ) . '(cn=' . $search . '))';
			
			$sr = ldap_search( $this->_conn, $this->_base_dn, $filter );
			
			$this->_set_last_query( $filter );
			
			return ldap_get_entries($this->_conn, $sr);
		}
		
		public function search_users( $field_to_search=null, $field_value='', $fields_to_show=null, $filter_group=null, $extra_query_info=null ) {
			if( is_null( $fields_to_show ) )
				$fields_to_show = array( 'samaccountname', 'displayname', 'mail', 'telephonenumber', 'department' );
			
			if( !empty( $field_to_search ) && !is_array( $field_to_search ) )
				$field_to_search = strtolower( $field_to_search );
			
			$filter_pre = '(&';
			$filter = array();
			
			if( is_array( $field_to_search ) && is_array( $field_value ) ) {
				$fields = array_combine( $field_to_search, $field_value );
				
				$filter['objectclass'] = !array_key_exists( 'objectclass', $fields ) ? '(objectClass=user)' : '(objectClass=' . $fields['objectclass'] . ')';
				$filter['samaccounttype'] = !array_key_exists( 'samaccounttype', $fields ) ? '(samaccounttype=' . ADLDAP_NORMAL_ACCOUNT . ')' : '(samaccounttype=' . $fields['samaccounttype'] . ')';
				$filter['objectcategory'] = !array_key_exists( 'objectcategory', $fields ) ? '(objectCategory=person)' : '(objectCategory=' . $fields['objectcategory'] . ')';
				
				$filters = array();
				foreach( $fields as $f=>$v ) {
					$filters[$f] = $this->build_user_search_filter( $f, $v );
				}
				$filter[] = '(|' . implode('', $filters ) . ')';
				
				if( !empty( $filter_group ) )
					$filter['group'] = '(memberof=cn=' . $filter_group . ',' . $this->_base_dn . ')';
				
				if( !array_key_exists( 'cn', $fields ) )
					$filter[] = '(cn=*)';
				
			} else {
				
				$filter['objectclass'] = ( 'objectclass' != $field_to_search ) ? '(objectClass=user)' : '(objectClass=' . $field_value . ')';
				$filter['samaccounttype'] = ( 'samaccounttype' != $field_to_search ) ? '(samaccounttype=' . ADLDAP_NORMAL_ACCOUNT . ')' : '(samaccounttype=' . $field_value . ')';
				$filter['objectcategory'] = ( 'objectcategory' != $field_to_search ) ? '(objectCategory=person)' : '(objectCategory=' . $field_value . ')';
				
				$filter[] = $this->build_user_search_filter( $field_to_search, $field_value );
				
				if( 'cn' != $field_to_search ) 
					$filter[] = '(cn=*)';
			}
			if( !empty( $extra_query_info ) )
				$filter[] = $extra_query_info;
			
			$filter = '(&' . implode( '', $filter ) . ')';
			
			$this->_set_last_query( $filter );
			
			$sr = ldap_search( $this->_conn, $this->_base_dn, $filter/*, $fields_to_show*/ );
			if( defined( 'WP_DEBUG' ) && WP_DEBUG )
				error_log( '[AD LDAP Debug]: Search Filter: ' . print_r( $filter, true ) );
			
			return ldap_get_entries($this->_conn, $sr);
		}
		
		public function build_user_search_filter( $field_to_search=null, $field_value='' ) {
			switch( $field_to_search ) {
				case 'objectclass':
				case 'samaccounttype':
				case 'objectcategory':
					break;
				
				case 'memberof':
					$filter = '(memberof=cn=' . $field_value . ',' . $this->_base_dn . ')';
					break;
					
				case 'cn':
				case 'department':
				case 'telephonenumber':
				case 'samaccountname':
				case 'sn':
				case 'givenname':
				case 'displayname':
				default:
					$filter = '(' . $field_to_search . '=*' . $field_value . '*)';
			}
			
			return $filter;
		}
		
		protected function _set_last_query( $q ) {
			$this->_last_query = $q;
		}
		
		function set_ldap_option( $name, $value ) {
			ldap_set_option( $this->_conn, $name, $value );
		}
		
		function get_ldap_option( $name, &$rt ) {
			ldap_get_option( $this->_conn, $name, $rt );
		}
	}
}
?>