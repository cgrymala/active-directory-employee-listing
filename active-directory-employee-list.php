<?php
/*
Plugin Name: Active Directory Employee Listing
Plugin URI: http://plugins.ten-321.com/active-directory-employee-list/
Description: Allows WordPress to retrieve and display a list of people from an active directory server
Version: 0.2.2a
Author: Curtiss Grymala
Author URI: http://ten-321.com/
License: GPL2

This plugin utilizes the adLDAP PHP class developed by Scott Barnett & Richard Hyland, available at http://adldap.sourceforge.net/
*/
/*  Copyright 2011  Curtiss Grymala  (email : cgrymala@umw.edu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action( 'plugins_loaded', 'init_active_directory_employee_list' );
add_action( 'plugins_loaded', 'add_adel_widget' );
function init_active_directory_employee_list() {
	global $active_directory_employee_list_object;
	
	$adel_file_loc = str_replace( basename( __FILE__ ), '', realpath( __FILE__ ) ) . 'classes/';
	if( file_exists( $adel_file_loc . 'class-active-directory-employee-list.php' ) )
		$found = true;
	
	if( !$found ) {
		$adel_file_loc = str_replace( basename( __FILE__ ), '', realpath( __FILE__ ) ) . 'active-directory-employee-list/classes/';
		if( file_exists( $adel_file_loc . 'class-active-directory-employee-list.php' ) )
			$found = true;
		else
			$adel_file_loc = '';
	}
	
	if( is_admin() ) {
		if( !class_exists( 'active_directory_employee_list_admin' ) )
			require_once( $adel_file_loc . 'class-active-directory-employee-list-admin.php' );
			
		$active_directory_employee_list_object = new active_directory_employee_list_admin;
	} else {
		if( !class_exists( 'active_directory_employee_list' ) )
			require_once( $adel_file_loc . 'class-active-directory-employee-list-output.php' );
			
		$active_directory_employee_list_object = new active_directory_employee_list_output;
	}
	
	return $active_directory_employee_list_object;
}

function add_adel_widget() {
	if( !class_exists( 'active_directory_employee_list_widget' ) )
		require_once( 'classes/class-active-directory-employee-list-widget.php' );
	
	add_action( 'widgets_init', 'register_active_directory_employee_list_widget' );
}

function register_active_directory_employee_list_widget() {
	register_widget( 'active_directory_employee_list_widget' );
}
?>