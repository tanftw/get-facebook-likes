<?php
/**
 * Default Plugin Settings
 * 
 * @return array
 */
function gfl_default_settings()
{
	$defaults = array(
		'actions' 	=> array( 'like_count', 'share_count', 'comment_count', 'total_count' ),
		'mode'		=> 'basic', // basic, advanced
		'app_id'    => '',
		'sdk_locale'=> 'en_US',  // Set JS SDK Locale
		'auto_add'  => true, // Auto add js sdk to wp_head
	);

	return apply_filters( 'gfl_default_settings', $defaults );
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
function gfl_likes( $post_id = null )
{
	$likes = gfl_facebook_count( 'fb_like_count', $post_id );

	return apply_filters( 'gfl_likes', $likes );
}


/**
 * Return likes/shares/comments count of given post
 * 
 * @param  string $action meta_key
 * @param  int $post_id (Optional) Post ID. Default: Current post
 * 
 * @return int Total Likes/Shares/Comments
 */
function gfl_facebook_count( $action = 'fb_like_count', $post_id = null, $number_format = true )
{
	if ( is_null( $post_id ) )
		$post_id = get_the_ID();

	$facebook_count = intval( get_post_meta( $post_id, $action, true ) );
	
	if ( $number_format )
		$facebook_count = gfl_number_format( $facebook_count );

	return $facebook_count;
}

/**
 * Convert to K, M, B if number is more than 10k
 * 
 * @param  Integer $number Numeric value
 * 
 * @return String Formatted number
 * @since  1.1.2
 */
function gfl_number_format( $number )
{
	if ( $number < 10000 )
        return number_format_i18n( $number );
    
    $alphabets = array( 1000000000 => 'B', 1000000 => 'M', 1000 => 'K' );

    foreach( $alphabets as $key => $value ) 
    {
    	$rounded = round( $number / $key, 1 );

    	if ( $number >= $key )
    	{
    		if ( $rounded < $number / $key )
    			return $rounded . $value . '+';

    		if ( $rounded == $number / $key )
    			return $rounded . $value;
    	}
    }
}

/**
 * Alias of gfl_facebook_count
 *
 * @since  1.1
 * 
 * @return int
 */
function gfl_count( $action = 'fb_like_count', $post_id = null )
{
	return gfl_facebook_count( $action, $post_id, true );
}

/**
 * Get field by action name
 * 
 * @return String $action
 */
function gfl_get_field( $action )
{
	if ( $action[strlen( $action ) - 1] == 's' )
		$action = rtrim( $action, 's' );

	if ( $action === 'all' )
		$action = 'total';
	
	return "fb_{$action}_count";
}