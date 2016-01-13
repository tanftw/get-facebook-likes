<?php

function gfl_get_setting( $setting_name = '' )
{
 	$settings = array(
		'actions' 	=> array( 'like_count', 'share_count', 'comment_count', 'total_count' ),
		'mode'		=> 'advanced'
	);

 	if ( ! empty( $settings ) && isset( $settings[$setting_name] ) )
 		return $settings[$setting_name];

	return $settings;
}

function the_likes()
{
	$likes = get_likes();

	echo $likes;
}

function get_likes( $post_id = null )
{
	$
}