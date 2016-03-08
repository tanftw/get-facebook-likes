<?php
/**
 * Get Facebook Likes Main Class
 * 
 * @author Tan Nguyen <tan@binaty.org>
 */
class GFL_Main
{
	/**
	 * Sort params
	 * 
	 * @var String
	 */
	public $fsortby;

	/**
	 * Constructor only to define hooks and register things
	 *
	 * @return void
	 */
	public function __construct()
	{
		$mode = gfl_setting( 'mode' );

		/** Update likes/shares/comments each time singular pages load when in basic mode */
		if ( $mode != 'advanced' ) {
			add_action( 'wp_head', array( $this, 'update_likes' ), 9999 );
		}
		else {
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );
			add_action( 'wp_ajax_nopriv_update_likes', array( $this, 'ajax_update_likes' ) );
		}

		add_action( 'wp_head', array( $this, 'add_facebook_js_sdk' ) );

		/** Create Meta Box to show total Likes/Shares/Comments if Meta Box plugin is installed */
		add_filter( 'rwmb_meta_boxes', array( $this, 'add_meta_boxes' ) );

		/** Add Most Favourited Content to Dashboard Widget */
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );

		/** Add custom sortable columns */
		add_action( 'manage_posts_custom_column', array( $this, 'total_liked_column_content' ) );
		add_filter( 'manage_posts_columns', array( $this, 'total_liked_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'total_liked_column_content' ) );
		add_filter( 'manage_pages_columns', array( $this, 'total_liked_column' ) );
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'sort_total_liked_column' ) );
		add_filter( 'manage_edit-page_sortable_columns', array( $this, 'sort_total_liked_column' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_likes' ) );

		/** Sort by views via url */
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_posts' ) );

		/** Register Shortcodes **/
		add_shortcode( 'likes', array( $this, 'likes_shortcode' ) );
		add_shortcode( 'shares', array( $this, 'shares_shortcode' ) );
		add_shortcode( 'comments', array( $this, 'comments_shortcode' ) );

		add_action( 'plugins_loaded', array( $this, 'i18n' ) );
	}

	/**
	 * Enqueue frontend js to listening to Facebook events and send ajax back to backend
	 * 
	 * @return void
	 */
	public function frontend_enqueue()
	{
		wp_enqueue_script( 'get-facebook-likes', GFL_JS_URL . 'gfl-main.min.js', array('jquery'), '1.0', true );

		wp_localize_script( 'get-facebook-likes', 'GFL', array( 
			'ajax_url' => admin_url( 'admin-ajax.php' )
		) );
	}

	/**
	 * Add Facebook JS SDK to wp_head if auto_add is checked
	 *
	 * @return  void
	 */
	public function add_facebook_js_sdk()
	{
		$auto_add 	= gfl_setting('auto_add');
		
		if ( ! $auto_add || gfl_setting('mode') != 'advanced' )
			return;

		$app_id 	= gfl_setting('app_id');

		$sdk_locale = gfl_setting('sdk_locale');
		$sdk_locale = empty( $sdk_locale ) ? 'en_US' : $sdk_locale;
		?>
	<script>
	  	window.fbAsyncInit = function() {
		    FB.init({
		    	<?php if ( ! empty( $app_id ) ) echo "appId	   :'{$app_id}'," ?>
		      	xfbml      : true,
		      	version    : 'v2.5'
		    });
            
            if (typeof GFL_Main != 'undefined')
                GFL_Main.init();
	  	};

	  	(function(d, s, id){
	     	var js, fjs = d.getElementsByTagName(s)[0];
	     	if (d.getElementById(id)) {return;}
	     	js = d.createElement(s); js.id = id;
	     	js.src = "//connect.facebook.net/<?php echo $sdk_locale ?>/sdk.js";
	     	fjs.parentNode.insertBefore(js, fjs);
	   	}(document, 'script', 'facebook-jssdk'));
	</script>
		<?php
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

	/**
	 * Set likes/shares/comments count to post meta
	 * 
	 * @param int $post_id Post ID
	 * @param Mixed $total Amount to set. If it's an array, then array will have format: action => count
	 * @param string $action fb_like_count, fb_share_count, or fb_comment_count
	 *
	 * @return void
	 */
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

	/**
	 * Create a GET request to Graph API EndPoint to get total likes/shares/comments of given URL
	 * 
	 * @param  String $url URL to get
	 * 
	 * @return Array Num of likes/shares/comments
	 */
	private function api_call_get_facebook_likes( $url )
	{
		if ( empty( $url ) )
			return;

		$graph_api_endpoint = "https://api.facebook.com/method/links.getStats?urls={$url}&format=json";

		$data = json_decode( file_get_contents( $graph_api_endpoint ) );

		$data = $data[0];
	
		$total = array();

		$actions = gfl_setting( 'actions' );

		foreach ( $actions as $action )
		{
			$total[$action] = $data->$action;
		}

		return $total;
	}

	/**
	 * Get current post id
	 * 
	 * @return int Post ID
	 */
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
			'id' 		=> 'get-facebook-likes',
			'title' 	=> 'Get Facebook Likes - Tracking',
			'priority' 	=> 'low',
			'fields' => array(
				array(
					'id'   => 'fb_like_count',
					'name' => 'Likes',
					'type' => 'number',
					'std'  => 0
				),
				array(
					'id'   => 'fb_comment_count',
					'name' => 'Comments',
					'type' => 'number',
					'std'  => 0
				),
				array(
					'id'   => 'fb_share_count',
					'name' => 'Shares',
					'type' => 'number',
					'std'  => 0
				),
				array(
					'id'   => 'fb_total_count',
					'name' => 'Total',
					'type' => 'number',
					'std'  => 0
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
			'post_type'				=> 'any',
			'ignore_sticky_posts' 	=> 1,
			'posts_per_page'		=> 10,
			'meta_key'				=> 'fb_total_count', 
		    'orderby'				=> 'meta_value_num', 
		    'order'					=> 'DESC'
		) );
		?>
		
		<table>
			<tr>
				<td width="80%"><h3>Post</h3></td>
				<td><span title="Likes" class="dashicons dashicons-thumbs-up"></span></td>
				<td><span title="Shares" class="dashicons dashicons-share"></span></td>
				<td><span title="Comments" class="dashicons dashicons-format-chat"></span></td>
				<td><span title="Total" class="dashicons dashicons-facebook-alt"></span></td>
			</tr>
		<?php
		if ( $loop->have_posts() ) :
			while ( $loop->have_posts() ) : $loop->the_post();
				?>
				<tr>
					<td><a href="<?php echo admin_url(); ?>post.php?post=<?php echo get_the_ID(); ?>&amp;action=edit"><?php the_title(); ?></a></td>
					<td><?php echo gfl_count( 'fb_like_count' ); ?></span></td>
					<td><?php echo gfl_count( 'fb_share_count' ); ?></span></td>
					<td><?php echo gfl_count( 'fb_comment_count' ); ?></span></td>
					<td><?php echo gfl_count( 'fb_total_count' ); ?></span></td>
				</tr>
				<?php
			endwhile;
		else :
		?>
			<td colspan="5"><?php  _e( 'No content to show', 'get-facebook-likes' );?></td>
		<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Update Facebook Likes, Shares, Comments count
	 * 
	 * @param  Integer $post_id Post ID
	 * 
	 * @return void
	 */
	public function update_likes( $post_id = '' )
	{
		if ( is_null( $post_id ) || empty( $post_id ) )
			$post_id 		= $this->get_post_id();

		if ( ! is_integer( $post_id ) )
			return;

		$url 			= get_permalink( $post_id );
				
		$facebook_likes = $this->api_call_get_facebook_likes( $url );

		$this->set( $post_id, $facebook_likes );
	}


	public function total_liked_column( $defaults )
	{
		$defaults['likes'] = __( 'Likes', 'gfl' );
    	return $defaults;
	}

	/**
	 * Put total likes of post to Likes column
	 * 
	 * @param  $column_name
	 * 
	 * @return void
	 */
	public function total_liked_column_content( $column_name )
	{
		if ( $column_name === 'likes' )
			echo gfl_likes();
	}

	/**
	 * Make Likes column sortable
	 * 
	 * @param  $defaults
	 * 
	 * @return $defaults
	 */
	public function sort_total_liked_column( $defaults )
	{
	    $defaults['likes'] = 'likes';
	    return $defaults;
	}

	/**
	 * Sort posts by likes
	 * 
	 * @param  $query
	 * @return void
	 */
	public function sort_by_likes( $query )
	{
		if( ! is_admin() )
	        return;

	    $orderby = $query->get( 'orderby' );

	    // Fixme: If fb_like_count doesn't exists, post will disappear
	    if ( 'likes' == $orderby )
	    {
	        $query->set( 'meta_key', 'fb_like_count' );
	        $query->set( 'orderby', 'meta_value_num' );
	    }
	}

	private function build_shortcode( $atts, $action )
	{
		$attributes = shortcode_atts( array( 'id' => 0 ), $atts );
	    
	    $id = intval( $attributes['id'] );
	    
	    if ( $id === 0 )
	        $id = get_the_ID();

	    return gfl_count( "fb_{$action}_count", $id );
	}

	/**
	 * Add `likes` shortcode
	 * 
	 * @param  $atts Shortcode Attributes
	 * 
	 * @return String Likes with custom format
	 */
	public function likes_shortcode( $atts )
	{	    
	    return $this->build_shortcode( $atts, 'like' );
	}

	public function shares_shortcode( $atts )
	{
		return $this->build_shortcode( $atts, 'share' );
	}

	public function comments_shortcode( $atts )
	{
		return $this->build_shortcode( $atts, 'comment' );
	}

	/**
	 * Define Internationalization
	 *
	 * @since  1.1 
	 * @return void
	 */
	public function i18n()
	{
		load_plugin_textdomain( 'get-facebook-likes', false, basename( GFL_DIR ) . '/lang/' );
	}

	/**
	 * Add query vars filter
	 * 
	 * @param $public_query_vars
	 */
	public function add_query_vars( $public_query_vars )
	{
		$public_query_vars[] = 'fsortby';
	    $public_query_vars[] = 'forderby';

	    return $public_query_vars;
	}

	/**
	 * Sort posts by fsortby & forderby parameters
	 */
	public function sort_posts( $local_wp_query ) 
	{
		// Set fsortby
		$this->fsortby = $local_wp_query->get( 'fsortby' );

	    if ( in_array( $this->fsortby, array( 'likes', 'shares', 'comments', 'all' ) ) ) 
	    {
	        add_filter('posts_fields', array( $this, 'posts_fields' ) );
	        add_filter('posts_join', array( $this, 'posts_join' ) );
	        add_filter('posts_where', array( $this, 'posts_where' ) );
	        add_filter('posts_orderby', array( $this, 'posts_orderby' ) );
	    } 
	    else 
	    {
	        remove_filter( 'posts_fields', array( $this, 'posts_fields' ) );
	        remove_filter( 'posts_join', array( $this, 'posts_join' ) );
	        remove_filter( 'posts_where', array( $this, 'posts_where' ) );
	        remove_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
	    }
	}

	public function posts_fields( $fields )
	{
		global $wpdb;
	    
	    $as = gfl_get_field( $this->fsortby );

	    $fields .= ", ({$wpdb->postmeta}.meta_value+0) AS {$as}";

	    return $fields;
	}

	public function posts_join( $content ) 
	{
	    global $wpdb;

	    $content .= " LEFT JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID";
	   
	    return $content;
	}
	
	public function posts_where( $content ) 
	{
	    global $wpdb;
	    
	    $meta_key = gfl_get_field( $this->fsortby );

	    $content .= " AND {$wpdb->postmeta}.meta_key = '{$meta_key}'";
	    
	    return $content;
	}
	
	public function posts_orderby( $content ) 
	{
	    $orderby = trim( addslashes( get_query_var( 'forderby' ) ) );

	    if ( empty( $orderby ) || ! in_array( $orderby, array( 'asc' ,'desc' ) ) )
	        $orderby = 'desc';
	    
	    $fsortby = gfl_get_field( $this->fsortby );

	    $content = " {$fsortby} $orderby";

	    return $content;
	}
}