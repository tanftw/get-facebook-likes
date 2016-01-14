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

		add_shortcode( 'likes', array( $this, 'likes_shortcode' ) );
		add_shortcode( 'shares', array( $this, 'shares_shortcode' ) );
		add_shortcode( 'comments', array( $this, 'comments_shortcode' ) );
	}

	/**
	 * Ajax Update Like method
	 * 
	 * @return Exit after complete
	 */
	public function ajax_update_likes()
	{
		if ( ! isset( $_REQUEST['url'] ) )
			return;

		$url 		= trim( $_REQUEST['url'] );

		$post_id 	= url_to_postid( $url );

		$this->update_likes( $post_id );

		exit;
	}

	public function frontend_enqueue()
	{
		wp_enqueue_script( 'get-facebook-likes', GFL_JS_URL . 'get-facebook-likes.js', array('jquery'), '1.0', true );

		wp_localize_script( 'get-facebook-likes', 'GFL', array( 
			'ajax_url' => admin_url( 'admin-ajax.php' )
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

		if ( get_the_ID() > 0 )
			return get_the_ID();

		if ( isset( $post->ID ) && is_int( $post->ID ) )
			return $post->ID;
	}

	/**
	 * Create a meta box which display total likes, shares and comments
	 * 
	 * @param array $meta_boxes Meta Boxes
	 * 
	 * @return array Meta Boxes
	 */
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

	/**
	 * Register Most Favourite Content Widget in Dashboard
	 *
	 * @return void
	 */
	public function add_dashboard_widgets()
	{
		wp_add_dashboard_widget(
            'most-favourite-content',
            'Most Favourite Content',
            array( $this, 'most_favourite_content' )
        );
	}

	/**
	 * Most Favourite Content Widget in Dashboard
	 * 
	 * @return void
	 */
	public function most_favourite_content()
	{
		global $wpdb;

		// Get top 10 favourite posts id by total likes + shares + comments
		$post_ids = $wpdb->get_col( 
			"SELECT post_id 
			FROM {$wpdb->prefix}postmeta 
			WHERE meta_key = 'fb_total_count' 
			ORDER BY meta_value DESC 
			LIMIT 10"
		);

		// Loop through and display them
		$loop = new WP_Query( array(
			'post__in' 				=> $post_ids,
			'ignore_sticky_posts' 	=> 1,
			'posts_per_page'		=> 10
		) );

		if ( $loop->have_posts() ) :
			while ( $loop->have_posts() ) : $loop->the_post();
				?>
				<h3><a href="<?php echo admin_url(); ?>post.php?post=1&amp;action=edit"><?php the_title(); ?></a> 
				<span class="count alignright">(<?php the_fb_action_count( 'fb_total_count' ); ?>)</span></h3>
				<?php
			endwhile;
		endif;
	}

	/**
	 * Update Facebook Likes, Shares, Comments count
	 * 
	 * @param  Integer $post_id Post ID
	 * 
	 * @return void
	 */
	public function update_likes( $post_id = null )
	{
		if ( is_null( $post_id ) && ! is_single( $post_id ) )
			return;

		if ( is_null( $post_id ) )
			$post_id 		= $this->get_post_id();
		
		$url 			= get_permalink( $post_id );
		
		$facebook_likes = $this->api_call_get_facebook_likes( $url );

		$this->set( $post_id, $facebook_likes );
	}
}
