// var casper = require('casper').create();
var system = require('system');
var http = require('http');
var querystring = require('querystring');

var FlowCollection = require('lib/FlowCollection').FlowCollection;
var OAuth = require('lib/OAuth').OAuth;


var config = {
	"url": "https://auth.dev.feideconnect.no/",
	"org": "feide.no",
	"username": "test",
	"password": system.env.password,
	"oauth": {
		"client_id": "34e87b41-ad1b-47ec-8d67-f6fb0a7b96f8",
		"client_secret": "ba1ea23f-04ff-47c2-86b8-448817c2021c",
		"redirect_uri": "http://andreas.uninettlabs.no/oauthtestlandingpage/callback.html",
		"scopes": ['groups', 'userinfo']
	}
};
if (system.env.CI) {
	config.url = 'http://127.0.0.1/';
}


for(var key in http) {
	console.log("HTTP method " + key);
}






var o = new OAuth(config);
var collection = new FlowCollection(casper, o);
collection.run();



