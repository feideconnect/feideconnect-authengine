"use strict";

var system = require('system');
var BaseFlow = require('../BaseFlow').BaseFlow;
var Step = require('../Step').Step;



var BaseOAuthFlow = BaseFlow.extend({
	"init": function(casper, oauth) {
		this.oauth = oauth;
		this._super(casper, oauth.getAuthorizationRequest() );
		this.title = 'Basic OAuth Authorization Code Flow';
	},

	"loadSteps": function(s) {
		for(var i = 0; i < s.length; i++) {
			this.steps.push(this[s[i]]());
		}
	},

	"prepare": function() {
		this.loadSteps([
			"stepSelectProvider", "stepSelectOrg", "stepLogin", "stepPreProdWarning", "stepLoginConsent",
			"stepSAMLResponse", "stepOAuthGrant", "stepRedirectURIcode"
		]);
	},

	"stepSelectProvider": function() {
		var flow = this;
		return new Step(this.casper, 'Select Login Provider', 2, {
			"evaluate": function(ctx) {
				return (ctx.page.url.indexOf('/disco') !== -1);
			},
			"execute": function(ctx, test) {
				test.assertTitle("Select your login provider", this.t(flow, "Check title") );
				test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				ctx.click('.list-group a');
			}
		});
	},

	"stepSelectOrg": function() {
		var flow = this;
		return new Step(this.casper, 'Select Org', 1, {
			"debug": false,
			"evaluate": function(ctx) {
				return (
					(ctx.page.url.indexOf('/simplesaml/module.php/feide/login.php') !== -1) &&
					(ctx.page.title === 'Choose affiliation')
				);
			},
			"execute": function(ctx, test) {
				test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				ctx.page.injectJs('https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js');
				ctx.evaluate(function(org) {

					$(document).ready(function() {
						$('#org').val(org).trigger('change');
						$("#submit").click();
						console.log("I've not clicked the submit button");
					});

				}, flow.oauth.config.org);
			}
		});
	},


	"stepLogin": function() {
		var flow = this;
		return new Step(this.casper, 'Login page (with credentials)', 1, {
			"debug": false,
			"evaluate": function(ctx) {
				return (
					(ctx.page.url.indexOf('/simplesaml/module.php/feide/login.php') !== -1) &&
					(ctx.page.title === 'Enter your username and password')
				);
			},
			"execute": function(ctx, test) {
				test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				ctx.page.injectJs('https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js');
				ctx.page.evaluate(function(username, password) {
					$('#username').val(username);
					$('#password').val(password);
					$('.submit').click();
				}, flow.oauth.config.username, flow.oauth.config.password);
			}
		});
	},

	"stepPreProdWarning": function() {
		var flow = this;
		return new Step(this.casper, 'Pre-prod Warning', 1, {
			"debug": false,
			"evaluate": function(ctx) {
				return (ctx.page.url.indexOf('/preprodwarning/showwarning.php') !== -1);
			},
			"execute": function(ctx, test) {
				test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				ctx.evaluate(function() {
					document.getElementById('yesbutton').click();
				});	
			}
		});
	},

	"stepLoginConsent": function() {
		var flow = this;
		return new Step(this.casper, 'Login consent', 1, {
			"debug": false,
			"evaluate": function(ctx) {
				return false;
			},
			"execute": function(ctx, test) {
				test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				ctx.evaluate(function() {
					document.getElementById('yesbutton').click();
				});	
			}
		});
	},

	"stepSAMLResponse": function() {
		var flow = this;
		return new Step(this.casper, 'SAML Response POST', 1, {
			// "debug": true, "html": true,
			"evaluate": function(ctx) {
				return false;
				// return (ctx.page.url.indexOf('/preprodwarning/showwarning.php') !== -1);
			},
			"execute": function(ctx, test) {
				test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				ctx.evaluate(function() {
					document.getElementById('yesbutton').click();
				});	
			}
		});
	},

	"stepOAuthGrant": function() {
		var flow = this;
		return new Step(this.casper, 'OAuth Grant display', 1, {
			// "debug": true, "html": true,
			"evaluate": function(ctx) {
				return false; 
				// return (ctx.page.url.indexOf('/preprodwarning/showwarning.php') !== -1);
			},
			"execute": function(ctx, test) {
				test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				ctx.evaluate(function() {
					document.getElementById('yesbutton').click();
				});	
			}
		});
	},

	"stepRedirectURIcode": function() {
		var flow = this;
		return new Step(this.casper, 'Redirect URI Code Flow', 1, {
			"debug": true, "html": true,
			"evaluate": function(ctx) {
				console.log("Chcking if URL matches " + flow.oauth.config.oauth.redirect_uri);
				return (ctx.page.url.indexOf(flow.oauth.config.oauth.redirect_uri) !== -1);
			},
			"execute": function(ctx, test) {
				test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				console.log("Execute...");
			}
		});
	}





});



exports.BaseOAuthFlow = BaseOAuthFlow;

