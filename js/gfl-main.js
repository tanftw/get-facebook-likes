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
    init: function () {
        
        FB.Event.subscribe('edge.create', function (url) {
            GFL_Main.fireAndUpdate('edge.create', url);
        });

        FB.Event.subscribe('edge.remove', function (url) {
            GFL_Main.fireAndUpdate('edge.remove', url);
        });

        FB.Event.subscribe('comment.create', function (url) {
            GFL_Main.fireAndUpdate('comment.create', url);
        });

        FB.Event.subscribe('comment.remove', function (url) {
            GFL_Main.fireAndUpdate('comment.remove', url);
        });
    },

    fireAndUpdate: function(event, url) {
        GFL.fire(event, url);
        GFL_Main.update(url);
    },

    /**
     * Send Ajax to update Likes, Shares and Comments
     * 
     * @return void
     */
    update: function (url) {
        
        // Fire event before update
        GFL.fire('update.before');

        jQuery.post(GFL.ajax_url, {
            action: 'nopriv_update_likes',
            url: url
        }, function (response) {
            // Fire response event. User can use response argument in their function.
            GFL.fire('update.response', response);
        });

        // Fire event after update
        GFL.fire('update.after');
    }
};