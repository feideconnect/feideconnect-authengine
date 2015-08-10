define(function(require, exports, module) {
	"use strict";

	var Class = require('./Class');

	var FeideWriter = Class.extend({

		"init": function(org, callback) {
			this.callback = callback;
			var returnURL = 'https://auth.dev.feideconnect.no/accountchooser/response';
			this.url = 'https://idp-test.feide.no/simplesaml/module.php/feide/preselectOrg.php?HomeOrg=' + encodeURIComponent(org) + '&ReturnTo=' + encodeURIComponent(returnURL);

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
