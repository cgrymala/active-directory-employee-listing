<?php
/**
 * Widget class and method definitions for the active-directory-employee-list plugin
 * @package Active-Directory-Employee-List
 * @subpackage Administration
 * @version 0.3
 */
if( !class_exists( 'active_directory_employee_list_output' ) )
	require_once( 'class-active-directory-employee-list-output.php' );

if( !class_exists( 'active_directory_employee_list_widget' ) ) {
	/**
	 * Class definition for the active_directory_employee_list_widget widget
	 */
	class active_directory_employee_list_widget extends WP_Widget {
		var $adelObj = null;
		
		function active_directory_employee_list_widget() {
			$this->adelObj = new active_directory_employee_list_output;
			if( is_admin() && stristr( basename( $_SERVER['REQUEST_URI'] ), 'widgets.php' ) )
				wp_enqueue_style( 'ad-employee-list-admin-style' );
			
			parent::WP_Widget( 'adel_list_widget', 'Active Directory Employee Widget', array( 'description' => __( 'Display either a list of people or details about a specific person as retrieved from the Active Directory', $this->adelObj->text_domain ) ), array( 'width' => 450 ) );
		}
		
		function form( $instance ) {
			/**
			 * Fields are:
			 * + title
			 * + username
			 * + group
			 * + fields
			 * + output_builder
			 */
?>
    <p>
        <label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title: ', $this->adelObj->text_domain ) ?></label> 
        <input class="widefat" type="text" name="<?php echo $this->get_field_name( 'title' ) ?>" id="<?php echo $this->get_field_id( 'title' ) ?>" value="<?php echo esc_attr( $instance['title'] ) ?>"/>
    </p>
    <p>
        <label for="<?php echo $this->get_field_id( 'username' ) ?>"><?php _e( 'Username: ', $this->adelObj->text_domain ) ?></label>
        <input class="widefat" type="text" name="<?php echo $this->get_field_name( 'username' ) ?>" id="<?php echo $this->get_field_id( 'username' ) ?>" value="<?php echo esc_attr( $instance['username'] ) ?>"/>
    </p>
    <p>
        <label for="<?php echo $this->get_field_id( 'group' ) ?>"><?php _e( 'User group to show: ', $this->adelObj->text_domain ) ?></label> 
        <select class="widefat" name="<?php echo $this->get_field_name( 'group' ) ?>" id="<?php echo $this->get_field_id( 'group' ) ?>">
        	<option<?php selected( $instance['group'], null ) ?> value=""><?php _e( '-- Please select an option --', $this->adelObj->text_domain ) ?></option>
<?php
			$groups = $this->adelObj->get_all_groups();
			foreach( $groups as $v=>$l ) {
?>
			<option value="<?php echo $v ?>"<?php selected( $instance['group'], $v ) ?>><?php echo $l ?></option>
<?php
			}
?>
        </select>
    </p>
    <p>
        <label for="<?php echo $this->get_field_id( 'fields' ) ?>"><?php _e( 'Fields to retrieve: ', $this->adelObj->text_domain ) ?></label> 
        <select class="widefat multiple" size="10" name="<?php echo $this->get_field_name( 'fields' ) ?>[]" id="<?php echo $this->get_field_id( 'fields' ) ?>" multiple="multiple">
<?php
			foreach( $this->adelObj->get_template_tags( false ) as $v=>$l ) {
			$s = in_array( $v, $instance['fields'] ) ? ' selected="selected"' : '';
?>
            <option value="<?php echo $v ?>"<?php echo $s ?> title="<?php echo esc_attr( $l ) ?>"><?php echo esc_attr( $v ) . ': ' . esc_attr( $l ) ?></option>
<?php
			}
?>
        </select>
    </p>
    <p>
        <label for="<?php echo $this->get_field_id( 'output_builder' ) ?>"><?php _e( 'Output builder: ', $this->adelObj->text_domain ) ?></label><br/>
        <textarea cols="30" rows="8" class="large-text" name="<?php echo $this->get_field_name( 'output_builder' ) ?>" id="<?php echo $this->get_field_id( 'output_builder' ) ?>"><?php echo esc_textarea( $instance['output_builder'] ) ?></textarea>
    </p>
<?php
		}
		
		function update( $new_instance, $old_instance ) {
			/**
			 * Fields are:
			 * + title
			 * + username
			 * + group
			 * + fields
			 * + output_builder
			 */
			$instance = $old_instance;
			$instance['title'] 			= empty( $new_instance['title'] ) ? null : strip_tags( $new_instance['title'] );
			$instance['username'] 		= empty( $new_instance['username'] ) ? null : strip_tags( $new_instance['username'] );
			$instance['group']			= empty( $new_instance['group'] ) ? null : strip_tags( $new_instance['group'] );
			$instance['fields']			= empty( $new_instance['fields'] ) ? null : $new_instance['fields'];
			$instance['output_builder']	= empty( $new_instance['output_builder'] ) ? null : stripslashes_deep( $new_instance['output_builder'] );
			
			return $instance;
		}
		
		function widget( $args, $instance ) {
			extract( $args );
			$title = apply_filters( 'widget_title', $instance['title'] );
			
			echo $before_widget;
			if( $title )
				echo $before_title . $title . $after_title;
			unset( $instance['title'] );
			
			/*$this->adelObj->_log( "\n<!-- Preparing to run the render shortcode function with the following parameters:\n", $instance, "\n-->\n" );*/
			
			echo $this->adelObj->render_shortcode( $instance );
			
			echo $after_widget;
		}
	}
}
?>