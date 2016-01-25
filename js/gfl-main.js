var GFL = GFL || {};

GFL.events = [];

/**
 * Subscribe an event. Similar to WP `add_action()`
 * 
 * @param  String   name  Event Name
 * @param  Mixed callback Function Callback
 * 
 * @return void
 */
GFL.subscribe = function (name, callback) {
    if (typeof GFL.events[name] == 'undefined')
        GFL.events[name] = []

    GFL.events[name].push(callback)
};

/**
 * Fire an event. Similar to WP `do_action`
 * 
 * @param  String name Event Name
 * @param  Mixed args Function args
 * 
 * @return void
 */
GFL.fire = function (name, args) {
    if (typeof GFL.events[name] != 'undefined') {
        for (var i = 0; i < GFL.events[name].length; ++i) {
            if (GFL.events[name][i] instanceof Function) {
                if (typeof args != 'undefined')
                    GFL.events[name][i](args);
                else
                    GFL.events[name][i]();
            }
        }
    }
};

/**
 * Get Facebook Like Object Class
 * 
 * @var GFL_Main
 */
var GFL_Main = {

    /**
     * Initital Method. Runs on page load.
     * 
     * @return void
     */
    init: function() {
        
        // List of Facebook Events to subscribe. We'll subscribe all of these events by default.
        var facebookEvents = ['edge.create', 'edge.remove', 'comment.create', 'comment.remove'];
        
        for (var i = 0; i < facebookEvents.length; i++)
        {
            FB.Event.subscribe(facebookEvents[i], function (url) {
            	// With each event. We'll allows other extensions to add event listener.
                GFL.fire(facebookEvents[i], url);

                GFL_Main.update(url);
            });
        }
    },

    /**
     * Send Ajax to update Likes, Shares and Comments
     * 
     * @return void
     */
    update: function(url) {

    	// Fire event before update
        GFL.fire('update.before');

        jQuery.post( GFL.ajax_url, {
            action: 'nopriv_update_likes',
            url: url
        }, function(response) {
        	// Fire response event. User can use response argument in their function.
            GFL.fire('update.response', response);
        });

        // Fire event after update
        GFL.fire('update.after');
    }
};