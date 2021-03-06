<?php
if( !defined('ABSPATH') ){
	exit;
}	

/**
 * Adds Curatescape_Widget widget.
 */
class Curatescape_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'curatescape_widget', // Base ID
			esc_html__( 'Curatescape Widget', 'wp_curatescape' ), // Name
			array( 'description' => esc_html__( 'A Curatescape Widget', 'wp_curatescape' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		if( ! empty( $instance['type'] ) && ! empty( $instance['number'] ) ){
			$p=get_posts(array(
				'post_type'=>$instance['type'],
				'numberposts'=>$instance['number'],
			));		
		}
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		if(isset($p)){
			echo '<ul>';
			foreach($p as $post){
				echo '<li><a href="'.get_permalink( $post ).'">'.$post->post_title.'</a></li>';
			}
			echo '</ul>';
		}
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Curatescape', 'wp_curatescape' );
		$number = ! empty( $instance['number'] ) ? $instance['number'] : 3;
		$type = ! empty( $instance['type'] ) ? $instance['type'] : 'stories';
		$select_options = array('stories'=>esc_html__('Stories','wp_curatescape'),'tours'=>esc_html__('Tours','wp_curatescape'))
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'wp_curatescape' ); ?></label> 
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'type' ) ); ?>"><?php esc_attr_e( 'Type:', 'wp_curatescape' ); ?></label>	
			<select name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>">';
				<?php foreach($select_options as $value=>$option){
					$selected = selected( $type === $value,true,false );
					echo '<option value="'.$value.'" '.$selected.'>'.$option.'</option>';
				}?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_attr_e( 'Number to show:', 'wp_curatescape' ); ?></label> 
			<input id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" min="1" max="10" value="<?php echo esc_attr( $number ); ?>">	
		</p>

		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['number'] = ( ! empty( $new_instance['number'] ) ) ? strip_tags($new_instance['number']) : 3;
		$instance['type'] = ( ! empty( $new_instance['type'] ) ) ? strip_tags($new_instance['type']) : 'stories';
		return $instance;
	}
}

function register_curatescape_widget() {
    register_widget( 'Curatescape_Widget' );
}
add_action( 'widgets_init', 'register_curatescape_widget' );