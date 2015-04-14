"use strict";

var BaseOAuthFlow = require('./BaseOAuthFlow').BaseOAuthFlow;
var Step = require('../Step').Step;






var BadRedirectURI = BaseOAuthFlow.extend({
	"init": function(casper, oauth) {
		this._super(casper, oauth);
		this.title = 'OAuth Authorization Code Flow with Incorrect Redirect URI';
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
		var that = this;
		return new Step(this.casper, 'Select Org', 0, {
			"debug": false,
			"evaluate": function(ctx) {
				return (
					(ctx.page.url.indexOf('/simplesaml/module.php/feide/login.php') !== -1) &&
					(ctx.page.title === 'Choose affiliation')
				);
			},
			"execute": function(ctx) {
				// test.assertHttpStatus(200, stepname + " Status code 200");
				ctx.page.injectJs('https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js');
				ctx.evaluate(function(org) {

					$(document).ready(function() {
						$('#org').val(org).trigger('change');
						$("#submit").click();
						console.log("I've not clicked the submit button");
					});

				}, that.oauth.config.org);
			}
		});
	},


	"stepLogin": function() {
		var that = this;
		return new Step(this.casper, 'Login page (with credentials)', 0, {
			"debug": false,
			"evaluate": function(ctx) {
				return (
					(ctx.page.url.indexOf('/simplesaml/module.php/feide/login.php') !== -1) &&
					(ctx.page.title === 'Enter your username and password')
				);
			},
			"execute": function(ctx) {

				ctx.page.injectJs('https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js');
				ctx.page.evaluate(function(username, password) {
					$('#username').val(username);
					$('#password').val(password);
					$('.submit').click();
				}, that.oauth.config.username, that.oauth.config.password);
			}
		});
	},

	"stepPreProdWarning": function() {
		var that = this;
		return new Step(this.casper, 'Pre-prod Warning', 0, {
			"debug": false,
			"evaluate": function(ctx) {
				return (ctx.page.url.indexOf('/preprodwarning/showwarning.php') !== -1);
			},
			"execute": function(ctx) {
				ctx.evaluate(function() {
					document.getElementById('yesbutton').click();
				});	
			}
		});
	},

	"stepLoginConsent": function() {
		var that = this;
		return new Step(this.casper, 'Login consent', 10, {
			"debug": false,
			"evaluate": function(ctx) {
				return false;
			},
			"execute": function(ctx) {
				ctx.evaluate(function() {
					document.getElementById('yesbutton').click();
				});	
			}
		});
	},

	"stepSAMLResponse": function() {
		var that = this;
		return new Step(this.casper, 'SAML Response POST', 0, {
			// "debug": true, "html": true,
			"evaluate": function(ctx) {
				return false;
				// return (ctx.page.url.indexOf('/preprodwarning/showwarning.php') !== -1);
			},
			"execute": function(ctx) {
				ctx.evaluate(function() {
					document.getElementById('yesbutton').click();
				});	
			}
		});
	},

	"stepOAuthGrant": function() {
		var that = this;
		return new Step(this.casper, 'OAuth Grant display', 0, {
			// "debug": true, "html": true,
			"evaluate": function(ctx) {
				return false; 
				// return (ctx.page.url.indexOf('/preprodwarning/showwarning.php') !== -1);
			},
			"execute": function(ctx) {
				ctx.evaluate(function() {
					document.getElementById('yesbutton').click();
				});	
			}
		});
	},

	"stepRedirectURIcode": function() {
		var that = this;
		return new Step(this.casper, 'Redirect URI Code Flow', 0, {
			"debug": true, "html": true,
			"evaluate": function(ctx) {
				console.log("Chcking if URL matches " + that.oauth.config.oauth.redirect_uri);
				return (ctx.page.url.indexOf(that.oauth.config.oauth.redirect_uri) !== -1);
			},
			"execute": function(ctx) {
				console.log("Execute...");
			}
		});
	}





});



exports.BadRedirectURI = BadRedirectURI;

