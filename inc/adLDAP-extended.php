<?php
if( !class_exists( 'adLDAP' ) )
	require_once( 'adLDAP.php' );

class adLDAPE extends adLDAP {
	var $_last_query = null;
	
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
		
		$sr = ldap_search( $this->_conn, $this->_base_dn, $filter, $fields );
		
		$this->_set_last_query( $filter );
		
		return ldap_get_entries($this->_conn, $sr);
	}
	
	public function search_users( $field_to_search=null, $field_value='', $fields_to_show=null, $filter_group=null ) {
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
		$filter = '(&' . implode( '', $filter ) . ')';
		
		$this->_set_last_query( $filter );
		
		$sr = ldap_search( $this->_conn, $this->_base_dn, $filter, $fields_to_show );
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
}
?>