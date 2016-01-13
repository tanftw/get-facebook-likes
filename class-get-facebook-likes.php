<?php

class Get_Facebook_Likes
{
	public function __construct()
	{
		add_action( 'wp_head', array( $this, 'update_likes' ), 9999 );

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );
	}

	public function frontend_enqueue()
	{

	}

	public function get()
	{

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
				if ( $count <= 0 )
					continue;

				update_post_meta( $post_id, $action, $count );
			}
		}

		do_action( 'after_update_facebook_likes' );
	}

	private function api_call_get_facebook_likes( $url )
	{
		$url = 'https://metabox.io';

		$graph_api_endpoint = "https://api.facebook.com/method/links.getStats?urls={$url}&format=json";

		$data = json_decode( file_get_contents( $graph_api_endpoint ) );
		$data = $data[0];
		
		$total = array();

		$actions = gfl_settings('actions');

		foreach ( $actions as $action )
		{
			$total[$action] = $data->$action;
		}

		return $total;
	}

	public function update_likes()
	{
		global $post;

		$post_id = null;

		if ( is_int( $post ) )
			$post_id = $post;

		if ( isset( $post->ID ) && is_int( $post->ID ) )
			$post_id = $post->ID;
		
		$url 			= get_permalink( $post_id );

		$facebook_likes = $this->api_call_get_facebook_likes( $url );

		$this->set( $post_id, $facebook_likes );
	}
}