<?php
/**
 * Default Plugin Settings
 * 
 * @return array
 */
function gfl_default_settings()
{
	return array(
		'actions' 	=> array( 'like_count', 'share_count', 'comment_count', 'total_count' ),
		'mode'		=> 'basic', // basic, advanced
		'app_id'    => '',
		'auto_add'  => true, // Auto add js sdk to wp_head
	);
}

/**
 * Get plugin setting
 * 
 * @param  Mixed $field Field name, if empty, return whole settings array
 * 
 * @return Mixed
 */
function gfl_setting( $field = null )
{
 	$settings = get_option( 'get_facebook_likes' );

 	$defaults = gfl_default_settings();

 	if ( empty( $settings ) || ! is_array( $settings ) )
 		$settings = $defaults;

	if ( is_null( $field ) )
		return $settings;

	if ( isset( $settings[$field] ) )
		return $settings[$field];

	if ( isset( $defaults[$field] ) )
		return $defaults[$field];

	return null;
}

/**
 * Get total likes of given post
 * 
 * @param  int $post_id (Optional) Post Id. Default: Current post
 * 
 * @return int Total Likes
 */
function get_likes( $post_id = null )
{
	$likes = fb_action_count( 'fb_like_count', $post_id );

	return apply_filters( 'the_likes', $likes );
}

/**
 * Print total likes
 * 
 * @param  int $post_id (Optional) Post Id. Default: Current post
 * 
 * @return void
 */
function the_likes( $post_id = null )
{
	echo get_likes( $post_id );
}

/**
 * Return likes/shares/comments count of given post
 * 
 * @param  string $action meta_key
 * @param  int $post_id (Optional) Post ID. Default: Current post
 * 
 * @return int Total Likes/Shares/Comments
 */
function fb_action_count( $action = 'fb_like_count', $post_id = null  )
{
	if ( is_null( $post_id ) )
		$post_id = get_the_ID();

	$action_count = intval( get_post_meta( $post_id, $action, true ) );
	
	return $action_count;
}