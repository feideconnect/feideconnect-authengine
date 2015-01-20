
var FlowEngine = require('./FlowEngine').FlowEngine;
var system = require('system');


var config = {
	"url": "https://auth.dev.feideconnect.no/",
	"org": "feide.no",
	"username": "test",
	"password": system.env.password,
	"oauth": {
		"client_id": "34e87b41-ad1b-47ec-8d67-f6fb0a7b96f8",
		"client_secret": "ba1ea23f-04ff-47c2-86b8-448817c2021c",
		"redirect_uri": "http://127.0.0.1/ci/callback",
		"scopes": ['groups', 'userinfo']
	}
};


if (system.env.CI) {

	config.url = 'http://127.0.0.1/';

}


function buildUrl(url, parameters){
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
}

var guid = function() {
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
		return v.toString(16);
	});
};



var CodeFlowAuthorizationRequest = function(config) {
	this.config = config;
	this.params = {
		"response_type": "code",
		"client_id": config.oauth.client_id,
		"redirect_uri": config.oauth.redirect_uri,
		"scope": config.oauth.scopes.join(' '),
		"state": guid()
	};
};
CodeFlowAuthorizationRequest.prototype.getURL = function() {
	return buildUrl(this.config.url + "oauth/authorization", this.params);
};




var LoginFlow = function(page) {

	console.log("Initializing LoginFlow");

	this.fe = new FlowEngine(page, function(data) {
		console.log("Login completed successfully.");
		console.log(data);
		phantom.exit();

	}, function() {
		console.log("Error");
		phantom.exit(1);
	});

	this.fe.addState('select_org', function(page) {
		// Detect script for select_org
		return page.evaluate(function() {
			return (document.getElementById('orgframe') !==  null);
		});
	}, function(page) {
		console.log("about to set org to " + config.org);
		page.evaluate(function(org) {
			$(document).ready(function() {
				$('#org').val(org).trigger('change');
				$("#submit").click();
				console.log("I've not clicked the submit button");
        	});
		}, config.org);

	}, ["login"]);


	this.fe.addState('login', function(page) {
		// Detect script for login
		return page.evaluate(function() {
			return (document.getElementById('username') !==  null);
		});
	}, function(page) {

		console.log('Logging in using user: ' + config.username + "  " + config.password);
		page.evaluate(function(username, password) {
			$('#username').val(username);
			$('#password').val(password);
			$('.submit').click();
		}, config.username, config.password);

	}, ["preprodwarning"]);



	this.fe.addState('preprodwarning', function(page) {
		// Detect script for login
		return (page.url.indexOf('/preprodwarning/showwarning.php') !== -1);

	}, function(page) {

		console.log('Clicking through preprodwarning');
		page.evaluate(function(username, password) {
				document.getElementById('yesbutton').click();
		}, config.username, config.password);

	}, ["consent", "postpage"]);




	this.fe.addState('consent', function(page) {
		// Detect script for login
		return page.evaluate(function() {
			return (document.getElementById('attributeheader') !==  null);
		});


	}, function(page) {

		console.log('PErforming consent.');
		page.evaluate(function(username, password) {
			document.getElementById('yesbutton').click();
		}, config.username, config.password);

	}, ["postpage", "grant"]);




	this.fe.addState('postpage', function(page) {
		// Detect script for login
		return page.evaluate(function() {
			return document.title === 'POST data';
		});

	}, function(page) {

		// page.includeJs("https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js", function() {
			// page.evaluate(function() {
			// 	// $("input[type=submit]").click();
			// });
		// });

		console.log('Got post data page.');
		// console.log(page.content);

	}, ["service"]);


	this.fe.addState('grant', function(page) {
		// Detect script for login
		return page.evaluate(function() {
			return (document.getElementById('submit') !==  null);
		});

	}, function(page) {

		page.includeJs("https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js", function() {
			page.evaluate(function() {

				$("#submit").click();
			});
		});
		

	}, ["login"]);



	this.fe.addState('service', function(page) {
		// Detect script for login
		return (page.url.indexOf('127.0.0.1') !== -1);
		// return page.evaluate(function() {
			
		// });

	}, function(page) {


		console.log("Page done");
		console.log(page.url);

		// page.includeJs("https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js", function() {
		// 	page.evaluate(function() {

		// 		$("#submit").click();
		// 	});
		// });
		

	}, null);



	var ar = new CodeFlowAuthorizationRequest(config);
	var url = ar.getURL();
	this.fe.go(url, ["select_org", "login"]);


};


exports.LoginFlow = LoginFlow;















