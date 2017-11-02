define(function(require, exports, module) {
    "use strict";

    var Class = require('../Class');
    var dust = require('../../components/dustjs-linkedin/dist/dust-full.min');
    require('templates');
    var template = 'templates/dust_providerlist';

    var ProviderListView = Class.extend({
        "init": function(app) {
            this.app = app;
            this.providers = [];
        },

        "setProviders": function(providers) {
            this.providers = providers;
        },


        "update": function(items, maxentries) {
            var that = this;
            return new Promise(function(resolve, reject) {
                // console.log("UPDATE YAY", items)
                var data = {
                    dict: that.app.dictionary
                }

                if (items.length > 0) {
                    data.providers = items.slice(0, maxentries).map(function(p) {
                        return p.getView();
                    });
                }
                data.hasMore = (items.length > maxentries);
                data.remaining = items.length - maxentries;
                // console.log("----- debug data view -----")
                // console.log(data);
                // console.log("------- ------- ------- ---")
                dust.renderSource(template, data, function(err, out) {
                    if (err) {
                        return reject(err);
                    }
                    console.log(out);
                    return resolve(out);
                });
            });

        }
    });


    return ProviderListView;
});
