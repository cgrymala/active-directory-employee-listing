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
		
		function deprecated_notice() {
?>
<div class="updated"><p><?php _e( 'The old Active Directory Employee Widget has been replaced with two separate widgets. In the next version of the ADEL plugin, the old version of the widget will be removed. Please update your widgets to use the new widgets. Thank you.', $this->adelObj->text_domain ) ?></p></div>
<?php
		}
		
		function active_directory_employee_list_widget() {
			$this->adelObj = new active_directory_employee_list_output;
			add_action( 'admin_print_styles-widgets.php', array( &$this, 'enqueue_admin_styles' ) );
			
			parent::WP_Widget( 'adel_list_widget', 'Active Directory Employee Widget', array( 'description' => __( 'This widget has been deprecated. Two new widgets, the "AD List Widget" and the "AD Employee Widget" have been provided, instead.', $this->adelObj->text_domain ) ), array( 'width' => 450 ) );
		}
		
		function enqueue_admin_styles() {
			add_action( 'admin_notices', array( &$this, 'deprecated_notice' ) );
			
			wp_enqueue_style( 'ad-employee-list-admin-style' );
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
			 $instance = array_merge( array( 'title'=>null, 'username'=>null, 'group'=>null, 'fields'=>array(), 'output_builder'=>null ), $instance );
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
			$this->adelObj->output_built = null;
			$this->adelObj->employee_list = null;
			$_REQUEST['widget_adeq'] = isset( $_REQUEST['widget_adeq'] ) ? $_REQUEST['widget_adeq'] : '';
			$_REQUEST['widget_adep'] = isset( $_REQUEST['widget_adep'] ) ? $_REQUEST['widget_adep'] : '';
			if( isset( $_REQUEST['widget_adeq'] ) ) {
				$tmp['adeq'] = isset( $_REQUEST['adeq'] ) ? $_REQUEST['adeq'] : null;
				$_REQUEST['adeq'] = $_REQUEST['widget_adeq'];
			}
			if( isset( $_REQUEST['widget_adep'] ) ) {
				$tmp['adep'] = isset( $_REQUEST['adep'] ) ? $_REQUEST['adep'] : null;
				$_REQUEST['adep'] = $_REQUEST['widget_adep'];
			}
			extract( $args );
			$title = apply_filters( 'widget_title', $instance['title'] );
			
			echo $before_widget;
			if( $title )
				echo $before_title . $title . $after_title;
			unset( $instance['title'] );
			
			/*$this->adelObj->_log( "\n<!-- Preparing to run the render shortcode function with the following parameters:\n", $instance, "\n-->\n" );*/
			
			echo str_replace( array( 'adep=', 'name="adeq"', 'id="adeq"' ), array( 'widget_adep=', 'name="widget_adeq"', 'id="widget_adeq"' ), $this->adelObj->render_shortcode( $instance ) );
			
			echo $after_widget;
			
			foreach( $tmp as $k=>$v ) {
				$_REQUEST[$k] = $v;
			}
			return;
		}
	}
}
?>