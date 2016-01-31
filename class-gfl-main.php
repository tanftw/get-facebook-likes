<?php
/**
 * Get Facebook Likes Main Class
 * 
 * @author Tan Nguyen <tan@binaty.org>
 */
class GFL_Main
{
	/**
	 * Basic or Advanced Mode
	 * 
	 * @var String
	 */
	public $mode;

	/**
	 * Constructor only to define hooks and register things
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->mode = gfl_setting( 'mode' );

		/** Update likes/shares/comments each time singular pages load when in basic mode */
		if ( $this->mode != 'advanced' )
			add_action( 'wp_head', array( $this, 'update_likes' ), 9999 );

		/** Update likes/shares/comments when users click on these button when in advanced mode */
		if ( $this->mode === 'advanced' )
		{
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
		wp_enqueue_script( 'get-facebook-likes', GFL_JS_URL . 'gfl-main.js', array('jquery'), '1.0', true );

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
		$auto_add 	= gfl_setting( 'auto_add' );
		
		if ( ! $auto_add || $this->mode != 'advanced' )
			return;

		$app_id 	= gfl_setting( 'app_id' );
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
	     	js.src = "//connect.facebook.net/en_US/sdk.js";
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

		if ( $loop->have_posts() ) :
			while ( $loop->have_posts() ) : $loop->the_post();
				?>
				<h3><a href="<?php echo admin_url(); ?>post.php?post=<?php echo get_the_ID(); ?>&amp;action=edit"><?php the_title(); ?></a> 
				<span class="count alignright">(<?php echo gfl_facebook_count( 'fb_total_count' ); ?>)</span></h3>
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

	    return gfl_facebook_count( "fb_{$action}_count", $id );
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
		return $this->build_shortcode( $atts, 'comments' );
	}

	public function i18n()
	{
		load_plugin_textdomain( 'gfl', false, basename( GFL_DIR ) . '/lang/' );
	}
}