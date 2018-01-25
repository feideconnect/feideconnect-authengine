define(function(require, exports, module) {
    "use strict";

    var Controller = require('./Controller');
    var Provider = require('./models/Provider');

    var DiscoveryFeedLoader = Controller.extend({
        "init": function() {
            this._callback = null;
            this.providers = {};
            this._super(undefined, true);
        },

        "initLoad": function() {
            // var that = this;
            return this._initLoaded();
            // return this.loadData()
            //     .then(this.proxy("_initLoaded"));
        },

        "loadData": function(country) {
            var that = this;

            if (this.providers[country]) {
                return Promise.resolve();
            }

            return new Promise(function(resolve, reject) {
                var url = '/metadata/providers/' + country;
                $.ajax({
                    dataType: "json",
                    url: url,
                    success: function(data) {
                        that.providers[country] = [];
                        for(var i = 0; i < data.length; i++) {
                            data[i].country = country;
                            that.providers[country].push(new Provider(data[i]));
                        }
                        // that.providers = data;
                        resolve(data);
                    },
                    error: function(err, a, b) {
                        console.error("error ", err, a, b);
                        reject(err);
                    }
                });
            });

        },

        // "executeCallback": function() {
        //     if (this._callback !== null) {
        //         this._callback(this.providers);
        //     }
        // },
        "getData": function(country) {
            return this.providers[country];
        }

    });
    return DiscoveryFeedLoader;




});
