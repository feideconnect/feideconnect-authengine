define(function(require, exports, module) {
	"use strict";

	var Class = require('./Class');

	var FeideWriter = Class.extend({

		"init": function(org, feideid) {
			this.feideid = feideid;

			var feideIdPEndpoints = {
				'https://idp-test.feide.no': 'https://idp-test.feide.no/simplesaml/module.php/feide/preselectOrg.php',
				'https://idp.feide.no': 'https://idp.feide.no/simplesaml/module.php/feide/preselectOrg.php'
			};

			if (!feideIdPEndpoints.hasOwnProperty(this.feideid)) {
				throw new Error("Bad Feide entityID. No configuration found.");
			}

			var returnURL = window.location.origin + '/accountchooser/response';
			this.url = feideIdPEndpoints[this.feideid] + '?HomeOrg=' + encodeURIComponent(org) + '&ReturnTo=' + encodeURIComponent(returnURL);

			var that = this;

			this._callback = null;
			$("#iloaded").on("click", function() {
				if (that._callback) {
					that._callback();
					that._callback = null;
				}

			});

	    },

	    "load": function() {

	    	var that = this;
			var iframe = '<iframe style="display: none" src="' + this.url + '"></iframe>';
			$("body").prepend(iframe);

			setTimeout(function() {

				if (that._callback) {
					that._callback();
					that._callback = null;
				}

			}, 1200);

			return this;

	    },
	    "onLoad": function(callback) {
	    	this._callback = callback;
	    	return this;
	    }

	});

	return FeideWriter;
});
