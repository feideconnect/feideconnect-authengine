"use strict";

var querystring = require("querystring");

var BaseFlow = require('../BaseFlow').BaseFlow;
var Step = require('../Step').Step;



var BaseOAuthFlow = BaseFlow.extend({
	"init": function(ph, oauth) {

		this.oauth = oauth;
		this._super(ph, oauth.getAuthorizationRequest() );
		this.title = 'Basic OAuth Authorization Code Flow';
	},

	"loadSteps": function(s) {
		for(var i = 0; i < s.length; i++) {
			this.steps.push(this[s[i]]());
		}
	},

	"prepare": function() {
		this.loadSteps([
			"stepSelectProvider", "stepSelectOrg",
			"stepLogin", 
			"stepPreProdWarning",

			"stepSAMLResponse",
			"stepOAuthGrant", 

			"stepRedirectURIcode",


			// "stepLoginConsent",
			// , 
		]);
		this._super();
	},

	"stepSelectProvider": function() {
		var flow = this;
		return new Step('Select Login Provider', {
			"evaluate": function(callback) {
				console.log("Evaluating select login provider");
				this.page.get("url", function(err, url) {
					console.log("The url is " + url);
					callback (url.indexOf('/disco') !== -1);
				})
				
			},
			"execute": function(callback) {
				console.log("Executng select login prvider");
				this.page.evaluate(function() {
					$(document).ready(function() {
						var href = $('.list-group a').eq(0).attr("href");
						window.location.href = href;
						// callback();
					});
				});
				
			}
		});
	},

	"stepSelectOrg": function() {
		var flow = this;
		return new Step('Select Org', {
			"debug": false,
			"evaluate": function(callback) {
				this.page.evaluate(function() {
					return {
						"title": document.title,
						"url": window.location.href
					};
				}, function(err, res) {
					callback (
						(res.url.indexOf('/simplesaml/module.php/feide/login.php') !== -1) &&
						(res.title === 'Choose affiliation')
					)
				});
			},
			"execute": function(callback) {
				// this.page.get('content', function (err,html) {
				// 	  console.log("Page HTML is: " + html);
				// 	})
				this.page.evaluate(function(org) {

					$(document).ready(function() {
						console.log("  ------> Org to set is " + org);
						$('#org').val(org); // .trigger('change');
						$(".submit").removeAttr("disabled");
						$(".submit").click();
						console.log("Org value is set to " + $('#org').val());
						console.log("I've not clicked the submit button");
					});

				}, function() {
					// callback();
				}, flow.oauth.config.org);
			}
		});
	},


	"stepLogin": function() {
		var flow = this;
		return new Step('Login page (with credentials)', {
			"debug": false,
			"evaluate": function(callback) {

				// this.page.get('content', function (err,html) {
				// 	  console.log("Page HTML is: " + html);
				// 	})

				this.page.evaluate(function() {
					return {
						"title": document.title,
						"url": window.location.href
					};
				}, function(err, res) {
					console.log("We got");
					console.log(res);
					callback (
						(res.url.indexOf('/simplesaml/module.php/feide/login.php') !== -1) &&
						(res.title === 'Enter your username and password')
					)
				});
			},
			"execute": function(callback) {
				this.page.evaluate(function(username, password) {

					$(document).ready(function() {
						$('#username').val(username);
						$('#password').val(password);
						$('.submit').click();
					});

				}, function() {
					// callback();
				}, flow.oauth.config.username, flow.oauth.config.password);

			}
		});
	},

	"stepPreProdWarning": function() {
		var flow = this;
		return new Step('Pre-prod Warning', {
			"debug": false,
			"evaluate": function(callback) {
				this.page.evaluate(function() {
					return {
						"title": document.title,
						"url": window.location.href
					};
				}, function(err, res) {
					callback (
						(res.url.indexOf('/preprodwarning/showwarning.php') !== -1)
					)
				});
			},
			"execute": function(callback) {
				// test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				this.page.evaluate(function() {

					// window.onload = function() {
					// 	console.log("On load fired!")
					// 	document.getElementById('yesbutton').click();
					// };
					setTimeout(function() {
						document.getElementById('yesbutton').click();
					}, 100);
					
					// 	$('#yesbutton').click();						
					// });

					// 
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
		return new Step('SAML Response POST', {
			// "debug": true, "html": true,
			"evaluate": function(callback) {
				// this.page.get('content', function (err,html) {
				// 	  console.log("Page HTML is: " + html);
				// 	})


				this.page.evaluate(function() {
					return {
						"title": document.title,
						"url": window.location.href
					};
				}, function(err, res) {
					callback (
						(res.title === 'POST data')
					)
				});
			},
			"execute": function(ctx, test) {
				this.page.evaluate(function() {
					document.getElementsByTagName('input')[0].click();
				});
				// test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				// ctx.evaluate(function() {
				// 	document.getElementById('yesbutton').click();
				// });	
			}
		});
	},

	"stepOAuthGrant": function() {
		var flow = this;
		return new Step('OAuth Grant display', {
			// "debug": true, "html": true,
			"evaluate": function(callback) {
				this.page.get('content', function (err,html) {
					console.log("Page HTML is: " + html);
				});

				this.page.evaluate(function() {
					return {
						"title": document.title,
						"url": window.location.href
					};
				}, function(err, res) {
					callback (
						(res.title === 'Authorization Required')
					)
				});

			},
			"execute": function(callback) {

				this.page.evaluate(function() {
					setTimeout(function() {
						document.getElementById('submit').click();
						// document.getElementsByTagName('input')[0].click();
					}, 100);
				});

			}
		});
	},

	"stepRedirectURIcode": function() {
		var flow = this;
		return new Step('Redirect URI Code Flow',{
			"debug": true, "html": true,
			"evaluate": function(callback) {

				this.page.get('content', function (err,html) {
					console.log("Page HTML is: " + html);
				});


				console.log("Chcking if URL matches " + flow.oauth.config.oauth.redirect_uri);
				this.page.evaluate(function() {
					return {
						"title": document.title,
						"url": window.location.href
					};
				}, function(err, res) {
					console.log("URL WAS " + res.url);
					callback (
						(res.url.indexOf(flow.oauth.config.oauth.redirect_uri) !== -1)
					)
				});
			},
			"execute": function(ctx, test) {
				// test.assertHttpStatus(200, this.t(flow, " Status code 200"));
				console.log("Execute...");

				this.page.evaluate(function() {
					setTimeout(function() {}, 1000);
					return {
						"title": document.title,
						"url": window.location.href,
						"qs": window.location.search
					};
				}, function(err, res) {
					console.log("We got the URL with the code " + res.qs);
					var decoded = querystring.parse(res.qs.substring(1));
					console.log(decoded);

					var code = decoded.code;
					flow.oauth.resolveCode(code, function(res) {
						console.log("RESULT WAS ,", res);	
					});
				});
			}
		});
	}





});



exports.BaseOAuthFlow = BaseOAuthFlow;

