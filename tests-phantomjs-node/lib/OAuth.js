"use strict";
var request = require('request');



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
	},

	"resolveCode": function(code) {
		var tokenEndpoint = this.config.url + 'oauth/token';
		var req = {
			"grant_type": "authorization_code",
			"code": code,
			"redirect_uri": this.config.oauth.redirect_uri,
			"client_id": this.config.oauth.client_id
		};
		request.post(tokenEndpoint, {"form": req, "auth": {"user": this.config.oauth.client_id, "pass": this.config.oauth.client_secret}}, function (error, response, body) {
			if (!error && response.statusCode == 200) {
				console.log(body) // Show the HTML for the Google homepage.
			} else {
				console.log("----- ERROR ----");
				console.log("Error code " + response.statusCode + " " + error);
			}
		})
	}
});


exports.OAuth = OAuth;
