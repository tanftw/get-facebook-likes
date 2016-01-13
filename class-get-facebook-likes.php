<?php

class Get_Facebook_Likes
{
	public function __construct()
	{
		// add_action( 'wp_head', array( $this, 'update_likes' ), 9999 );

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );

		add_action( 'wp_ajax_nopriv_update_likes', array( $this, 'ajax_update_likes' ) );
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

	public function set( $post_id, $total, $action = 'like_count' )
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

				update_post_meta( $post_id, $action, $count );
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

	public function update_likes( $post_id = null )
	{
		if ( is_null( $post_id ) )
			$post_id 		= $this->get_post_id();
		
		$url 			= get_permalink( $post_id );
		
		$facebook_likes = $this->api_call_get_facebook_likes( $url );

		$this->set( $post_id, $facebook_likes );
	}
}