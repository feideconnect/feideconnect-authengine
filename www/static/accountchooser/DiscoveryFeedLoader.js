define(function(require, exports, module) {
	"use strict";

	var Class = require('./Class');



    var DiscoveryFeedLoader = Class.extend({
    	"init": function() {
    		var that = this;

    		this.providers = [];

    		console.log("INITING DiscoveryFeedLoader");

    		this.initialized = false;
    		this.initialize();

    	},


    	"initialize": function() {
    		var that = this;
    		this.initialized = true;
    		this.loadData();

    	},

    	"loadData": function() {
    		
    		var that = this;
    		var url = 'https://api.discojuice.org/feed/edugain';
    		$.ajax({
				dataType: "json",
				url: url,
				success: function(data) {
					
					that.providers = data;
					that.executeCallback();

				},
				error: function(err, a, b) {
					console.error("error ", err, a, b);
				}
			});
    	},

		"executeCallback": function() {
			if (this._callback !== null) {
				this._callback(this.providers);
			}
		},

		"onUpdate": function(callback) {
			this._callback = callback;
		}

    });
    return DiscoveryFeedLoader;




});