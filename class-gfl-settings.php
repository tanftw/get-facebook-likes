<?php
/**
 * Settings Page for Get Facebook Likes
 *
 * @author Tan Nguyen <tan@binaty.org>
 */
class GFL_Settings
{
	/**
	 * Constructor only to define hooks
	 *
	 * @return void
	 */
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * Create admin menu under Settings
	 * 
	 * @return void
	 */
	public function admin_menu()
	{
		add_options_page( 
			__( 'Get Facebook Likes', 'gfl' ), 
			__( 'Get Facebook Likes', 'gfl' ), 
			'manage_options', 
			'get-facebook-likes', 
			array( $this, 'admin_page' ) 
		);
	}

	/**
	 * All plugin settings saved in this method
	 * 
	 * @return Redirect
	 */
	public function admin_init()
	{
		register_setting( 'get_facebook_likes', 'get_facebook_likes_settings' );

		if ( ! isset( $_POST['_page_now'] ) || $_POST['_page_now'] != 'get-facebook-likes' )
			return;

		$settings = array();

		$settings['mode'] = isset( $_POST['mode'] ) ? trim( $_POST['mode'] ) : 'basic';
		$settings['app_id'] = isset( $_POST['app_id'] ) ? trim( $_POST['app_id'] ) : '';
		$settings['auto_add'] = isset( $_POST['auto_add'] ) ? true : false;
		$settings['sdk_locale'] = trim( $_POST['sdk_locale'] );
		
		update_option( 'get_facebook_likes', $settings );

		// Redirect with success message
		$_POST['_wp_http_referer'] = add_query_arg( 'success', 'true', $_POST['_wp_http_referer'] );
		wp_redirect( $_POST['_wp_http_referer'] );
		exit;
	}

	/**
	 * Render Settings Page Content
	 * 
	 * @return void
	 */
	public function admin_page()
	{
		?>
		<script type="text/javascript">
		jQuery( function($) {
			$('[name="mode"], #auto_add').change(function (){
				var modeSelected = $('[name="mode"]:checked').val();
				var autoAdd 	 = $('#auto_add').is(':checked');

				if ( modeSelected === 'advanced' ) 
				{
					$('#auto_add_section, #app_id_section').show();

					if (autoAdd) {
						$('#app_id_section, #sdk_locale_section').show();
						$('#setup-guide').hide();
					}
					else {
						$('#app_id_section, #sdk_locale_section').hide();
						$('#setup-guide').show();
					}
				}
				else 
				{
					$('#setup-guide, #auto_add_section, #app_id_section, #sdk_locale_section').hide();
				}
			});

			$('[name="mode"], #auto_add').trigger('change');
		});
		</script>

		<style type="text/css">
			#setup-guide{
				margin: 30px 0;
				padding: 0 30px 30px 30px;
				border: 1px solid #eee;
			}
			#setup-guide hr{
				border: none;
				border-top: 1px solid #eee;
			}
			#setup-guide pre{
				background: #ddd;
				padding: 15px 0;
			}
		</style>

		<div class="wrap">
			<h2><?php _e( 'Get Facebook Likes', 'gfl' ); ?></h2>
			
			<?php 
			// Display success message when settings saved
			if ( isset( $_GET['success'] ) ) : ?>
			<div id="message" class="updated notice is-dismissible">
				<p><?php _e( 'Settings <strong>saved</strong>.', 'gfl' ); ?></p>
				<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
			</div>
			<?php endif; ?>
			
			<form action="options.php" method="post" id="poststuff">
				<?php settings_fields( 'get_facebook_likes' ); ?>
				<div id="post-body" class="metabox-holder columns-2">
					
					<div id="postbox-container-2" class="postbox-container">

						<div class="meta-box-sortables">
							<div class="postbox">
			                	<div class="handlediv" title="Click to toggle"> <br></div>
			                  	<h3 class="hndle ui-sortable-handle"><?php _e( 'General Settings', 'gfl' ); ?></h3>
			                  	<div class="inside">
			                    	<table class="form-table">
			                    		<tr valign="top">
			                    			<th><?php _e( 'Mode', 'gfl' ); ?></th>
			                    			<td>
			                    				<div>

				                    				<label>
														<input type="radio" name="mode" value="basic" <?php if ( gfl_setting( 'mode' ) == 'basic' ) echo 'checked'; ?>> <?php _e( 'Basic', 'gfl' ); ?>
				                    				</label>
				                    				<p class="description">
				                    					<?php 
				                    					_e( 'Update Likes, Shares and Comments count each time the page load. <br>
				                    					<strong>Pros:</strong> Works immediately, no need to setup app, api key and custom js <br>
				                    					<strong>Cons:</strong> Less accurate, a little bit slower page load', 'gfl' );
				                    					?>
				                    				</p>
			                    				</div>

			                    				<br>
												<section>
				                    				<label>
														<input type="radio" name="mode" value="advanced" <?php if ( gfl_setting( 'mode' ) == 'advanced' ) echo 'checked'; ?>> <?php _e( 'Advanced', 'gfl' ); ?> <code><?php _e( 'Recommended', 'gfl' ); ?></code>
				                    				</label>
			                    					<p class="description">
			                    						<?php
			                    						_e( 'Update Likes, Shares and Comments count when user hit these buttons. <br>
			                    						<strong>Pros:</strong> More accurate, faster page load. <br>
														<strong>Cons:</strong> Requires you setup JS SDK.
														', 'gfl' );
														?>
			                    					</p>
			                    				</section>
												<br>

												<section id="auto_add_section">

													<label for="auto_add">
														<input type="checkbox" id="auto_add" value="1" name="auto_add" <?php if ( gfl_setting( 'auto_add' ) === true ) echo 'checked'; ?>> 
														<?php _e( 'Auto add JS SDK to <code>wp_head</code>', 'gfl' ); ?>
													</label>
													<p class="description">
														<?php 
														_e( "This will add Facebook JS SDK between <code>head</code> tags so you don't have to edit theme files.<br>
														Please make sure that no other FB JS SDK installed.<br>
														Uncheck this checkbox in case you already have FB JS SDK installed or you want to setup by yourself.", 'gfl' );
														?>
													</p>
													
												</section>

												<br>

												<section id="app_id_section">
													<label for="app_id"><code><?php _e( 'App ID', 'gfl' ); ?></code> <?php _e( 'Optional', 'gfl' ); ?></label><br>
													<input type="text" id="app_id" name="app_id" value="<?php echo gfl_setting( 'app_id' ); ?>"> 
													<p class="description"><?php _e( 'Your Facebook App ID', 'gfl' ); ?> <?php _e( 'Recommended', 'gfl' ); ?></p>
												</section>
												
												<section id="sdk_locale_section">
													<label for="sdk_locale"><code><?php _e( 'SDK Locale', 'gfl' ); ?></code> <?php _e( 'Optional', 'gfl' ); ?></label><br>
													<input type="text" id="sdk_locale" name="sdk_locale" value="<?php echo gfl_setting('sdk_locale' ); ?>">
													<p class="description">
														<?php _e( 'In case you\'re using Facebook SDK in your language', 'gfl' ); ?>
													</p>
												</section>

			                    				<section id="setup-guide">
													<h3><?php _e( 'Setup Guide', 'gfl' ); ?></h3>
													<p class="description"><?php _e( "If you've already checked <code>Auto add JS SDK to wp_head</code>. Please ignore this guide.", 'gfl' ); ?></p>
													<hr>
													<h4><?php _e ( 'Step 1', 'gfl' ); ?></h4>
													<p>
														<?php _e ( 'If you\'ve already setup Facebook JS SDK, ignore this step. Otherwise, follow <a href="https://developers.facebook.com/docs/javascript/quickstart/v2.5">FB Quick Start Guide</a>', 'gfl' ); ?>
													</p>
													<hr>
													<h4><?php _e( 'Step 2', 'gfl' ); ?></h4>
													<p>
														<?php _e( 'Add <code>GFL_Main.init();</code> after <code>FB.init();</code> in <code>window.fbAsyncInit</code> method, like so', 'gfl' ); ?>
	<pre>
	window.fbAsyncInit = function() {
	    FB.init({
	      appId      : 'your-app-id',
	      xfbml      : true,
	      version    : 'v2.5'
	    });

	    // <?php _e( 'Add this line', 'gfl' ); ?>
	    
	    GFL_Main.init();
	};
  </pre>
													<?php _e( "That's all", 'gfl' ); ?>
													</p>
			                    				</section>
			                    			</td>
			                    		</tr>
			                    	</table>
			                  	</div><!--.inside-->
			              	</div><!--.postbox-->
						</div><!--.metaboxes-->
					</div><!--.postbox-container2-->

				</div><!--#post-body-->
				<br class="clear">

				<input type="hidden" name="_page_now" value="get-facebook-likes">
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

new GFL_Settings;