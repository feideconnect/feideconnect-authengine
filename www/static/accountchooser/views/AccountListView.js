define(function(require, exports, module) {
    "use strict";

    var Class = require('../Class');
    // require('dustjs');
    // require('../templates');
    var template = 'templates/dust_accountlist.dust';

    var AccountListView = Class.extend({
        "init": function(app) {
            this.app = app;
        },

        "update": function(data) {
            var that = this;
            return new Promise(function(resolve, reject) {
                // console.log("----- debug data view -----")
                // console.log(data);
                // console.log("------- ------- ------- ---")
                // console.log("ABout to render")
                // console.log(dust)
                dust.render(template, data, function(err, out) {
                    if (err) {
                        return reject(err);
                    }
                    return resolve(out);
                });
            });

        }
    });


    return AccountListView;
});
