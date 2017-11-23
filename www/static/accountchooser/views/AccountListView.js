define(function(require, exports, module) {
    "use strict";

    var Class = require('../Class');

    var dust = require('dustjs');
    require('../templates');
    var template = 'templates/dust_accountlist';

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
