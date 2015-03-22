
// var process = require('process');
var phantom = require('node-phantom-simple');
var http = require('http');
var querystring = require('querystring');

var FlowCollection = require('./lib/FlowCollection').FlowCollection;
var OAuth = require('./lib/OAuth').OAuth;


var config = {
	"url": "https://auth.dev.feideconnect.no/",
	"org": "feide.no",
	"username": "test",
	"password": process.env.password,
	"oauth": {
		"client_id": "34e87b41-ad1b-47ec-8d67-f6fb0a7b96f8",
		"client_secret": "ba1ea23f-04ff-47c2-86b8-448817c2021c",
		"redirect_uri": "http://andreas.uninettlabs.no/oauthtestlandingpage/callback.html",
		"scopes": ['groups', 'userinfo']
	}
};
if (process.env.CI) {
	config.url = 'http://127.0.0.1/';
}

console.log("----- ");
console.log(" OAuth config ");
console.log(config);
console.log("----- ");

var o = new OAuth(config);


it('Prepare env', function(done) {

	phantom.create(function(err, ph) {

		var collection = new FlowCollection(ph, o);
		collection.run();

	});



});




