define(function(require, exports, module) {
	"use strict";

	var Controller = require('./Controller');
	var Provider = require('./models/Provider');

    var DiscoveryFeedLoader = Controller.extend({
    	"init": function() {
    		var that = this;

    		this._callback = null;
    		this.providers = [];

    		this._super(undefined, true);
    	},

		"initLoad": function() {
			var that = this;
			return this.loadData()
				.then(this.proxy("_initLoaded"));
		},
    	"loadData": function() {
    		var that = this;

    		return Promise.resolve([]);
    		
			// return new Promise(function(resolve, reject) {
			// 	var url = 'https://api.discojuice.org/feed/edugain';
			// 	$.ajax({
			// 		dataType: "json",
			// 		url: url,
			// 		success: function(data) {
			// 			for(var i = 0; i < data.length; i++) {
			// 				that.providers.push(new Provider(data[i]));
			// 			}
			// 			// that.providers = data;
			// 			resolve(data);
			// 		},
			// 		error: function(err, a, b) {
			// 			console.error("error ", err, a, b);
			// 			reject(err);
			// 		}
			// 	});
			// });

    	},
		"executeCallback": function() {
			if (this._callback !== null) {
				this._callback(this.providers);
			}
		},
		"getData": function() {
			return this.providers;
		}

    });
    return DiscoveryFeedLoader;




});