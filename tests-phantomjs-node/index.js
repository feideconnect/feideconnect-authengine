
// Built-in Node.js modules
var assert = require('assert');

// Third party libraries
var phantom = require('node-phantom-simple');
// var phantom = require('phantom');
var Promise = require('promise');
var Class = require('./lib/Class').Class;

// Local libraries
var FlowCollection = require('./lib/FlowCollection').FlowCollection;
var OAuth = require('./lib/OAuth').OAuth;





// var emptyPromise = new Promise(function(resolve) { 
// 	resolve(); 
// });

// // Credits: http://trevorburnham.com/presentations/flow-control-with-promises/#/16
// var promiseWaterfall = function(tasks) {
// 	var finalTaskPromise = tasks.reduce(function(prevTaskPromise, task) {
// 		return prevTaskPromise.then(task);
// 	}, emptyPromise);
// 	return finalTaskPromise;
// }



// var a = [
// 	function() {
// 		return new Promise(function(resolve) {
// 			setTimeout(function() {
// 				console.log("Completed");
// 				resolve();
// 			}, 1000);
// 		}).then(function() {
// 			return new Promise(function(resolve) {
// 				setTimeout(function() {
// 					console.log("Completed (1)");
// 					resolve();
// 				}, 1000);
// 			});
// 		});
// 	},
// 	function() {
// 		return new Promise(function(resolve) {
// 			setTimeout(function() {
// 				console.log("Completed");
// 				resolve();
// 			}, 1000);
// 		});
// 	}
// ]


// promiseWaterfall(a)
// 	.then(function() {
// 		console.log(" --- All completed");
// 	});





// return;




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


describe('Feide Connect test suite collection', function() {

	it('Completed.', function(done) {

		phantom.create(function(err, ph) {

			assert(typeof ph === 'object', 'Phantom object is present');
			var collection = new FlowCollection(ph, o);
			collection.run()
				.then(function() {
					console.log("Now we're completed with the whole thing");
					done();
				});

			// it('Waiting for test suite to complete', function(done) {
				
				// collection.completed(done);
			// });

			// done();

	// 
			// done();

		}, parameters);



	});

	var parameters = {
		"parameters": {
			'ignore-ssl-errors': 'yes', 
			"ssl-protocol": "any"
		}
	};


	// it("Whole testsuite completed", function(done) {
	// 	assert(true);
	// 	done();
	// });

});






