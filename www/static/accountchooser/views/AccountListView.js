define(function(require, exports, module) {
    "use strict";

    var Class = require('../Class');

    require('../../components/dustjs-linkedin/dist/dust-full.min');
    // console.log("DUST", dust)

    var AccountListView = Class.extend({
        "init": function(app) {
            this.app = app;
            this.src = document.getElementById('accountlist-template').textContent;
        },

        "update": function(data) {
            var that = this;
            return new Promise(function(resolve, reject) {
                // console.log("----- debug data view -----")
                // console.log(data);
                // console.log("------- ------- ------- ---")
                dust.renderSource(that.src, data, function(err, out) {
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
