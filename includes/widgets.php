<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Widgets class.
 * 
 * @class Post_Views_Counter_Widgets
 */
class Post_Views_Counter_Widgets {

	public function __construct() {
		// actions
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register widgets.
	 */
	public function register_widgets() {
		register_widget( 'Post_Views_Counter_List_Widget' );
	}

}

/**
 * Post_Views_Counter_List_Widget class.
 * 
 * @class Post_Views_Counter_List_Widget
 */
class Post_Views_Counter_List_Widget extends WP_Widget {

	private $pvc_defaults;
	private $pvc_post_types;
	private $pvc_order_types;
	private $pvc_list_types;
	private $pvc_image_sizes;

	public function __construct() {
		parent::__construct(
			'Post_Views_Counter_List_Widget', __( 'Most Viewed Posts', 'post-views-counter' ), array(
				'description' => __( 'Displays a list of the most viewed posts', 'post-views-counter' )
			)
		);

		$this->pvc_defaults = array(
			'title'					=> __( 'Most Viewed Posts', 'post-views-counter' ),
			'number_of_posts'		=> 5,
			'thumbnail_size'		=> 'thumbnail',
			'post_type'				=> array(),
			'order'					=> 'desc',
			'list_type'				=> 'unordered',
			'show_post_views'		=> true,
			'show_post_thumbnail'	=> false,
			'show_post_excerpt'		=> false,
			'show_post_author'		=> false,
			'no_posts_message'		=> __( 'No Posts found', 'post-views-counter' )
		);

		$this->pvc_order_types = array(
			'asc'	 => __( 'Ascending', 'post-views-counter' ),
			'desc'	 => __( 'Descending', 'post-views-counter' )
		);

		$this->pvc_list_types = array(
			'unordered'	 => __( 'Unordered list', 'post-views-counter' ),
			'ordered'	 => __( 'Ordered list', 'post-views-counter' )
		);

		$this->pvc_image_sizes = array_merge( array( 'full' ), get_intermediate_image_sizes() );

		// sort image sizes by name, ascending
		sort( $this->pvc_image_sizes, SORT_STRING );

		add_action( 'wp_loaded', array( $this, 'load_post_types' ) );
	}

	/**
	 * Get selected post types.
	 */
	public function load_post_types() {

		if ( ! is_admin() )
			return;

		$this->pvc_post_types = Post_Views_Counter()->settings->post_types;
	}

	/**
	 * Display widget.
	 * 
	 * @param array $args
	 * @param object $instance
	 */
	public function widget( $args, $instance ) {
		$instance['title'] = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		$html = $args['before_widget'] . ( ! empty( $instance['title'] ) ? $args['before_title'] . $instance['title'] . $args['after_title'] : '');
		$html .= pvc_most_viewed_posts( $instance, false );
		$html .= $args['after_widget'];

		echo $html;
	}

	/** Render widget form.
	 * 
	 * @param object $instance
	 * @return mixed
	 */
	public function form( $instance ) {
		$html = '
	<p>
		<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'post-views-counter' ) . ':</label>
		<input id="' . $this->get_field_id( 'title' ) . '" class="widefat" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( isset( $instance['title'] ) ? $instance['title'] : $this->pvc_defaults['title']  ) . '" />
	</p>
	<p>
		<label>' . __( 'Post Types', 'post-views-counter' ) . ':</label><br />';

		foreach ( $this->pvc_post_types as $post_type => $post_type_name ) {
			$html .= '
		<input id="' . $this->get_field_id( 'post_type' ) . '-' . $post_type . '" type="checkbox" name="' . $this->get_field_name( 'post_type' ) . '[]" value="' . $post_type . '" ' . checked( ( ! isset( $instance['post_type'] ) ? true : in_array( $post_type, $instance['post_type'], true ) ), true, false ) . '><label for="' . $this->get_field_id( 'post_type' ) . '-' . $post_type . '">' . esc_html( $post_type_name ) . '</label>';
		}

		$show_post_thumbnail = isset( $instance['show_post_thumbnail'] ) ? $instance['show_post_thumbnail'] : $this->pvc_defaults['show_post_thumbnail'];

		$html .= '
	</p>
	<p>
		<label for="' . $this->get_field_id( 'number_of_posts' ) . '">' . __( 'Number of posts to show', 'post-views-counter' ) . ':</label>
		<input id="' . $this->get_field_id( 'number_of_posts' ) . '" name="' . $this->get_field_name( 'number_of_posts' ) . '" type="text" size="3" value="' . esc_attr( isset( $instance['number_of_posts'] ) ? $instance['number_of_posts'] : $this->pvc_defaults['number_of_posts']  ) . '" />
	</p>
	<p>
		<label for="' . $this->get_field_id( 'no_posts_message' ) . '">' . __( 'No posts message', 'post-views-counter' ) . ':</label>
		<input id="' . $this->get_field_id( 'no_posts_message' ) . '" class="widefat" type="text" name="' . $this->get_field_name( 'no_posts_message' ) . '" value="' . esc_attr( isset( $instance['no_posts_message'] ) ? $instance['no_posts_message'] : $this->pvc_defaults['no_posts_message']  ) . '" />
	</p>
	<p>
		<label for="' . $this->get_field_id( 'order' ) . '">' . __( 'Order', 'post-views-counter' ) . ':</label>
		<select id="' . $this->get_field_id( 'order' ) . '" name="' . $this->get_field_name( 'order' ) . '">';

		foreach ( $this->pvc_order_types as $id => $order ) {
			$html .= '
		<option value="' . esc_attr( $id ) . '" ' . selected( $id, ( isset( $instance['order'] ) ? $instance['order'] : $this->pvc_defaults['order'] ), false ) . '>' . $order . '</option>';
		}

		$html .= '
		</select>
	</p>
	<p>
		<label for="' . $this->get_field_id( 'list_type' ) . '">' . __( 'Display Style', 'post-views-counter' ) . ':</label>
		<select id="' . $this->get_field_id( 'list_type' ) . '" name="' . $this->get_field_name( 'list_type' ) . '">';

		foreach ( $this->pvc_list_types as $id => $list_type ) {
			$html .= '
		<option value="' . esc_attr( $id ) . '" ' . selected( $id, ( isset( $instance['list_type'] ) ? $instance['list_type'] : $this->pvc_defaults['list_type'] ), false ) . '>' . $list_type . '</option>';
		}

		$html .= '
		</select>
	</p>
	<p>
		<input id="' . $this->get_field_id( 'show_post_views' ) . '" type="checkbox" name="' . $this->get_field_name( 'show_post_views' ) . '" ' . checked( true, (isset( $instance['show_post_views'] ) ? $instance['show_post_views'] : $this->pvc_defaults['show_post_views'] ), false ) . ' /> <label for="' . $this->get_field_id( 'show_post_views' ) . '">' . __( 'Display post views?', 'post-views-counter' ) . '</label>
		<br />
		<input id="' . $this->get_field_id( 'show_post_excerpt' ) . '" type="checkbox" name="' . $this->get_field_name( 'show_post_excerpt' ) . '" ' . checked( true, (isset( $instance['show_post_excerpt'] ) ? $instance['show_post_excerpt'] : $this->pvc_defaults['show_post_excerpt'] ), false ) . ' /> <label for="' . $this->get_field_id( 'show_post_excerpt' ) . '">' . __( 'Display post excerpt?', 'post-views-counter' ) . '</label>
		<br />
		<input id="' . $this->get_field_id( 'show_post_author' ) . '" type="checkbox" name="' . $this->get_field_name( 'show_post_author' ) . '" ' . checked( true, (isset( $instance['show_post_author'] ) ? $instance['show_post_author'] : $this->pvc_defaults['show_post_author'] ), false ) . ' /> <label for="' . $this->get_field_id( 'show_post_author' ) . '">' . __( 'Display post author?', 'post-views-counter' ) . '</label>
		<br />
		<input id="' . $this->get_field_id( 'show_post_thumbnail' ) . '" class="pvc-show-post-thumbnail" type="checkbox" name="' . $this->get_field_name( 'show_post_thumbnail' ) . '" ' . checked( true, $show_post_thumbnail, false ) . ' /> <label for="' . $this->get_field_id( 'show_post_thumbnail' ) . '">' . __( 'Display post thumbnail?', 'post-views-counter' ) . '</label>
	</p>
	<p class="pvc-post-thumbnail-size"' . ( $show_post_thumbnail ? '' : ' style="display: none;"' ) . '>
		<label for="' . $this->get_field_id( 'thumbnail_size' ) . '">' . __( 'Thumbnail size', 'post-views-counter' ) . ':</label>
		<select id="' . $this->get_field_id( 'thumbnail_size' ) . '" name="' . $this->get_field_name( 'thumbnail_size' ) . '">';

		$size_type = isset( $instance['thumbnail_size'] ) ? $instance['thumbnail_size'] : $this->pvc_defaults['thumbnail_size'];

		foreach ( $this->pvc_image_sizes as $size ) {
			$html .= '
		<option value="' . esc_attr( $size ) . '" ' . selected( $size, $size_type, false ) . '>' . $size . '</option>';
		}

		$html .= '
		</select>
	</p>';

		echo $html;
	}

	/**
	 * Save widget form.
	 * 
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		// number of posts
		$old_instance['number_of_posts'] = (int) (isset( $new_instance['number_of_posts'] ) ? $new_instance['number_of_posts'] : $this->pvc_defaults['number_of_posts']);

		// order
		$old_instance['order'] = isset( $new_instance['order'] ) && in_array( $new_instance['order'], array_keys( $this->pvc_order_types ), true ) ? $new_instance['order'] : $this->pvc_defaults['order'];

		// list type
		$old_instance['list_type'] = isset( $new_instance['list_type'] ) && in_array( $new_instance['list_type'], array_keys( $this->pvc_list_types ), true ) ? $new_instance['list_type'] : $this->pvc_defaults['list_type'];

		// thumbnail size
		$old_instance['thumbnail_size'] = isset( $new_instance['thumbnail_size'] ) && in_array( $new_instance['thumbnail_size'], $this->pvc_image_sizes, true ) ? $new_instance['thumbnail_size'] : $this->pvc_defaults['thumbnail_size'];

		// booleans
		$old_instance['show_post_views'] = isset( $new_instance['show_post_views'] );
		$old_instance['show_post_thumbnail'] = isset( $new_instance['show_post_thumbnail'] );
		$old_instance['show_post_excerpt'] = isset( $new_instance['show_post_excerpt'] );
		$old_instance['show_post_author'] = isset( $new_instance['show_post_author'] );

		// texts
		$old_instance['title'] = sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : $this->pvc_defaults['title']  );
		$old_instance['no_posts_message'] = sanitize_text_field( isset( $new_instance['no_posts_message'] ) ? $new_instance['no_posts_message'] : $this->pvc_defaults['no_posts_message']  );

		// post types
		if ( isset( $new_instance['post_type'] ) ) {
			$post_types = array();

			foreach ( $new_instance['post_type'] as $post_type ) {
				if ( isset( $this->pvc_post_types[$post_type] ) )
					$post_types[] = $post_type;
			}

			$old_instance['post_type'] = array_unique( $post_types );
		} else
			$old_instance['post_type'] = array( 'post' );

		return $old_instance;
	}

}
