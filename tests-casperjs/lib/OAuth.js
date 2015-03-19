"use strict";

var system = require('system');
var Class = require('./Class').Class;

var OAuth = Class.extend({
	"init": function (config) {
		this.config = config;
	},
	"guid": function () {
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
			return v.toString(16);
		});
	},

	"buildUrl": function(url, parameters){
		var qs = "";
		for(var key in parameters) {
			var value = parameters[key];
			qs += encodeURIComponent(key) + "=" + encodeURIComponent(value) + "&";
		}
		if (qs.length > 0){
			qs = qs.substring(0, qs.length-1); //chop off last "&"
			url = url + "?" + qs;
		}
		return url;
	},

	"getAuthorizationRequest": function () {
		var params = {
			"response_type": "code",
			"client_id": this.config.oauth.client_id,
			"redirect_uri": this.config.oauth.redirect_uri,
			"scope": this.config.oauth.scopes.join(' '),
			"state": this.guid()
		};
		var authorizationurl = this.buildUrl(this.config.url + 'oauth/authorization', params);
		return authorizationurl;
	}
});


exports.OAuth = OAuth;
