<?php

class Get_Facebook_Likes
{
	public function __construct()
	{
		// add_action( 'wp_head', array( $this, 'update_likes' ), 9999 );

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );

		add_action( 'wp_ajax_nopriv_update_likes', array( $this, 'ajax_update_likes' ) );

		add_filter( 'rwmb_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
	}

	public function ajax_update_likes()
	{
		if ( ! isset( $_REQUEST['post_id'] ) )
			return;

		$post_id = intval( $_REQUEST['post_id'] );

		$this->update_likes( $post_id );

		exit;
	}

	public function frontend_enqueue()
	{
		if ( ! is_singular() )
			return;

		$post_id = $this->get_post_id();
		
		if ( ! is_integer( $post_id ) )
			return;

		wp_enqueue_script( 'get-facebook-likes', GFL_JS_URL . 'get-facebook-likes.js', array('jquery'), '1.0', true );

		wp_localize_script( 'get-facebook-likes', 'GFL', array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ), 
			'post_id' => $post_id 
		) );
	}

	public function get()
	{
		//
	}

	public function set( $post_id, $total, $action = 'fb_like_count' )
	{
		do_action( 'before_update_facebook_likes' );

		if ( is_integer( $total ) )
			update_post_meta( $post_id, $action, $total );
		
		if ( is_array( $total ) )
		{
			foreach ( $total as $action => $count )
			{
				if ( $count < 0 )
					continue;

				update_post_meta( $post_id, 'fb_' . $action, $count );
			}
		}

		do_action( 'after_update_facebook_likes' );
	}

	private function api_call_get_facebook_likes( $url )
	{
		$graph_api_endpoint = "https://api.facebook.com/method/links.getStats?urls={$url}&format=json";

		$data = json_decode( file_get_contents( $graph_api_endpoint ) );

		$data = $data[0];
	
		$total = array();

		$actions = gfl_setting('actions');

		foreach ( $actions as $action )
		{
			$total[$action] = $data->$action;
		}

		return $total;
	}

	private function get_post_id()
	{
		global $post;

		$post_id = null;

		if ( is_int( $post ) )
			$post_id = $post;

		if ( isset( $post->ID ) && is_int( $post->ID ) )
			$post_id = $post->ID;

		return $post_id;
	}

	private function is_meta_box_plugin_active()
	{
		return class_exists( 'RW_Meta_Box' );
	}

	public function add_meta_boxes( $meta_boxes )
	{
		$meta_boxes[] = array(
			'id' => 'get-facebook-likes',
			'title' => '<em>Get Facebook Likes</em> Tracking',
			'fields' => array(
				array(
					'id'   => 'fb_like_count',
					'name' => 'Likes',
					'type' => 'number',
					'readonly' => true
				),
				array(
					'id'   => 'fb_comment_count',
					'name' => 'Comments',
					'type' => 'number',
					'readonly' => true
				),
				array(
					'id'   => 'fb_share_count',
					'name' => 'Shares',
					'type' => 'number',
					'readonly' => true
				),
				array(
					'id'   => 'fb_total_count',
					'name' => 'Total',
					'type' => 'number',
					'readonly' => true
				)
			)
		);

		return $meta_boxes;
	}

	public function add_dashboard_widgets()
	{
		wp_add_dashboard_widget(
            'most-favourite-content',         // Widget slug.
            'Most Favourite Content',         // Title.
            array( $this, 'most_favourite_content' )
        );
	}

	public function most_favourite_content()
	{
		global $wpdb;

		$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'fb_total_count' ORDER BY meta_value DESC LIMIT 10" );

		$loop = new WP_Query( array(
			'post__in' 				=> $post_ids,
			'ignore_sticky_posts' 	=> 1,
			'posts_per_page'		=> 10
		) );

		$i = 0;

		if ( $loop->have_posts() ) :
			while ( $loop->have_posts() ) : $loop->the_post();
				?>
				<h3><a href="<?php echo admin_url(); ?>post.php?post=1&amp;action=edit"><?php the_title(); ?></a> 
				<span class="count alignright">(<?php the_fb_action_count( 'fb_total_count' ); ?>)</span></h3>
				<?php
			endwhile;
		endif;
	}

	public function update_likes( $post_id = null )
	{
		if ( is_null( $post_id ) )
			$post_id 		= $this->get_post_id();
		
		$url 			= get_permalink( $post_id );
		
		$facebook_likes = $this->api_call_get_facebook_likes( $url );

		$this->set( $post_id, $facebook_likes );
	}
}
