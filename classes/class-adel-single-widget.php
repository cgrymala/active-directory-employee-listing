<?php
/**
 * A widget to display a single AD member's contact information
 * @package Active-Directory-Employee-List
 * @subpackage Administration
 * @version 0.3
 */
if( !class_exists( 'active_directory_employee_list_output' ) )
	require_once( 'class-active-directory-employee-list-output.php' );

if( !class_exists( 'ADEL_Single_Widget' ) ) {
	/**
	 * Class definition for the widget that displays a single AD user
	 */
	class ADEL_Single_Widget extends WP_Widget {
		/**
		 * An active_directory_employee_list object
		 */
		var $adelObj = null;
		
		function ADEL_Single_Widget() {
			return self::__construct();
		}
		
		function __construct() {
			$this->adelObj = new active_directory_employee_list_output;
			add_action( 'admin_print_styles-widgets.php', array( &$this, 'enqueue_admin_styles' ) );
			add_action( 'admin_print_scripts-widget.php', array( &$this, 'enqueue_admin_styles' ) );
			
			$widget_ops = array( 'classname' => 'adel-employee', 'description' => 'Display information about a specific user in active directory.' );
			$control_ops = array( 'width' => 450, 'height' => 350, 'id_base' => 'adel-employee-widget' );
			parent::WP_Widget( 'adel-employee-widget', 'AD Employee Widget', $widget_ops, $control_ops );
		}
		
		function enqueue_admin_styles() {
			wp_enqueue_style( 'ad-employee-list-admin-style' );
			wp_enqueue_script( 'ad-employee-list-admin' );
			add_action( 'admin_print_footer_scripts', array( &$this, 'send_admin_js_vars' ) );
		}
		
		function send_admin_js_vars() {
			print( "\n<!-- ADEL Widget JS Vars -->\n" );
			print( "\n<script type=\"text/javascript\">/* <![CDATA[ */\n" );
			print( "var adelData = { 'presets' : " . json_encode( $this->adelObj->_get_output_presets() ) . ", 'reqdfields' : " . json_encode( array_flip( $this->adelObj->_need_to_retrieve ) ) . " }" );
			print( "\n/* ]]> */</script>\n" );
			print( "\n<!--/ ADEL Widget JS Vars -->\n" );
		}
		
		function widget( $args, $instance ) {
			$this->adelObj->_get_options();
			
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
			
			echo str_replace( array( 'adep=', 'name="adeq"', 'id="adeq"' ), array( 'widget_adep=', 'name="widget_adeq"', 'id="widget_adeq"' ), $this->adelObj->render_shortcode( $instance ) );
			
			echo $after_widget;
			
			foreach( $tmp as $k=>$v ) {
				$_REQUEST[$k] = $v;
			}
			return;
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
			$instance['group']			= null;
			$instance['preset']			= empty( $new_instance['preset'] ) ? null : $new_instance['preset'];
			$instance['fields']			= empty( $new_instance['fields'] ) ? null : $new_instance['fields'];
			$instance['output_builder']	= empty( $new_instance['output_builder'] ) ? null : stripslashes_deep( $new_instance['output_builder'] );
			
			return $instance;
		}
		
		function form( $instance ) {
			$this->adelObj->_get_options();
			
			/**
			 * Fields are:
			 * + title
			 * + username
			 * + fields
			 * + output_builder
			 */
			 $instance = array_merge( array( 'title'=>null, 'username'=>null, 'group'=>null, 'fields'=>array(), 'output_builder'=>null, 'preset'=>'' ), $instance );
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
    	<label for="<?php $this->get_field_id( 'preset' ) ?>"><?php _e( 'Information to display:' ) ?></label>
        <select class="widefat adel-preset-selector" name="<?php echo $this->get_field_name( 'preset' ) ?>" id="<?php echo $this->get_field_id( 'preset' ) ?>">
        	<option value=""<?php echo empty( $instance['preset'] ) ? '' : ' selected="selected"' ?>><?php _e( '-- Select a preset --', $this->adelObj->text_domain ) ?></option>
<?php
			$presets = $this->adelObj->_get_output_presets();
			foreach( $presets as $v=>$l ) {
?>
			<option value="<?php echo esc_attr( $v ) ?>"<?php selected( $instance['preset'], $v ) ?>><?php echo esc_attr( $l['name'] ) ?></option>
<?php
			}
?>
        </select>
    </p>
    <p class="show-if-js"><a class="adel-widget-controls-show-advanced" href="#<?php echo $this->get_field_id( 'advanced-opts' ) ?>"><?php _e( 'Advanced formatting options', $this->adelObj->text_domain ) ?></a></p>
    <div class="hide-if-js" id="<?php echo $this->get_field_id( 'advanced-opts' ) ?>">
        <p>
            <label for="<?php echo $this->get_field_id( 'fields' ) ?>"><?php _e( 'Fields to display: ', $this->adelObj->text_domain ) ?></label> 
            <select class="widefat multiple adel-field-selector" size="10" name="<?php echo $this->get_field_name( 'fields' ) ?>[]" id="<?php echo $this->get_field_id( 'fields' ) ?>" multiple="multiple">
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
            <textarea cols="30" rows="8" class="large-text adel-output-builder" name="<?php echo $this->get_field_name( 'output_builder' ) ?>" id="<?php echo $this->get_field_id( 'output_builder' ) ?>"><?php echo esc_textarea( $instance['output_builder'] ) ?></textarea>
        </p>
     </div>
<?php
		}
		
	}
}
?>