/**
 * Get Facebook Like Object Class
 */
var GetFacebookLikes = {

	/**
	 * Initital Method
	 * 
	 * @return void
	 */
	init: function() {
		// Listening users like, share, comment event. 
		// If users did, then send Ajax call to update post meta
		FB.Event.subscribe('edge.create', GetFacebookLikes.update);
		FB.Event.subscribe('edge.remove', GetFacebookLikes.update);
		FB.Event.subscribe('comment.create', GetFacebookLikes.update);
		FB.Event.subscribe('comment.remove', GetFacebookLikes.update);
	},

	/**
	 * Send Ajax to update Likes, Shares and Comments
	 * 
	 * @return void
	 */
	update: function() {
		jQuery.post( GFL.ajax_url, {
			action: 'nopriv_update_likes',
			post_id: GFL.post_id
		}, function(response) {
			console.log(response);
		});
	}
};