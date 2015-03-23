// var phantom = require('phantom');
// var phantom = require('phantom');

var assert = require("assert")
var Class = require('./Class').Class;
var Promise = require('promise');
// var phantom = require('phantom');


var emptyPromise = new Promise(function(resolve) { 
	// console.log(" At least first empty promise was fulfilled");
	resolve(); 
});

// Credits: http://trevorburnham.com/presentations/flow-control-with-promises/#/16
var promiseWaterfall = function(tasks) {
	var finalTaskPromise = tasks.reduce(function(prevTaskPromise, task) {
		return prevTaskPromise.then(function() {
			return task;
		});
	}, emptyPromise);
	return finalTaskPromise;
}


var BaseFlow = Class.extend({
	"init": function(ph, starturl) {
		this.ph = ph;
		this.starturl = starturl;
		this.title = 'Base flow';
		this.steps = [];
		this.pointer = 0;

		this.isCompleted = false;

		this.pageLoadQueue = [];
		this.pageLoadQueued = false;

		this.page = null;

	},
	"initialize": function() {
		var that = this;


		// return new Promise(function(resolve, reject) {
		//   that.ph.createPage(function (page) {
		//     page.open("http://uninett.no", function (status) {
		//       console.log("opened google? ", status);
		//       page.evaluate(function () { return document.title; }, function (result) {
		//         console.log('Page title is ' + result);
		//         that.ph.exit();
		//       });
		//     });
		//   });
		// });


		return new Promise(function(resolve, reject) {
			that.ph.createPage(function(err, page) {
				// console.log("Preparing with a new page for " + that.title );
				that.page = page;

				// that.page.onUrlChanged = function(url) {
				// 	console.log('URL change [' + url + ']');
				// }
				that.page.onConsoleMessage = function(msg) {
					console.log('[' + that.title + '] ›' + msg);
				};

				that.page.onLoadFinished = (function(x) {
					return function(status) {
						// console.log("   [XXXXXXX] [--------] Loaded finnished. ");
						that.pageLoaded();
					};
				})(that);

				resolve();

			});
		});
	},

	"prepare": function() {
		// The prepare function is set up with the steps...!
	},

	"start": function() {
		var that = this;

		// console.log("  ----] Starting by going to " + this.starturl);

		return new Promise(function(resolve, reject) {
			that.page.open(that.starturl, function(status) {
				// console.log("Loaded status for [" + that.starturl + "] " + status);
				resolve();
				// if (status === "success") {
				// 	resolve();
				// } else {
				// 	reject(new Error("Failed to load " + that.starturl + ": " + status));
				// }

				// that.page.evaluate(function() {
				// 	return {
				// 		"title": document.title,
				// 		"url": window.location.href,
				// 		// "w": window
				// 	};
				// }, function(res) {
				// 	console.log("======================================================================================================");
				// 	console.log("    =====> URL WAS " + res.url);
				// 	console.log(res);
				// });

			});



		});

	},


	"executeSteps": function() {

		// console.log("  ----] Execute steps now");
		var that = this;

		var steppromises = that.steps.map(function(step) {
				return that.waitforPageLoad()
					.then(function() {
						console.log("   [ Wait for page load triggered... next step..] ");
					})
					.then(function() {
						var stepx = step;
						return stepx.execute.call(stepx);
					})
					.then(function() {
						// console.log("    ======= ONE STEP COMPELTED  ==========");
					});
		
			});

		// console.log("steppromises", JSON.stringify(steppromises, undefined, 2));



		var pw = promiseWaterfall(
			steppromises
		);

		// console.log(pw);
		return pw
			.then(function() {
				// console.log("------------------------------------> Now we are done");
			});

	},

	// Is executed when there is no more steps...
	"completed": function() {
		console.log("We are now complete. Have no more steps prepared.");
		this.isCompleted = true;

		if (this.onCompletedCallback && typeof this.onCompletedCallback === 'function') {
			this.onCompletedCallback();
		}
	},

	"pageLoaded": function() {
		var that = this;
		console.log("   ______________________________________________________________________");
		console.log("   [ PAGE LOADED ] There are " + this.pageLoadQueue.length + " steps left");

		this.page.evaluate(function() {
			return document.location.href;
		}, function(err, res) {

			console.log("   URL is [" + res + "]");

			if (that.pageLoadQueue.length >0) {
				var x = that.pageLoadQueue.shift();
				if (typeof x === 'function') {
					x();
				}
				that.pageLoadQueued = false;
			} else {
				that.pageLoadQueued = true;
			}

		});


	},

	"waitforCompleted": function() {
		var that = this;
		return new Promise(function(resolve, reject) {
			if (that.isCompleted) {
				resolve();
			}
			that.onCompletedCallback = resolve;
		});
	},

	"waitforPageLoad": function() {
		var that = this;
		return new Promise(function(resolve, reject) {
			// console.log("    SETTING WAIT FOR PAGE load");

			if (that.pageLoadQueued) {
				that.pageLoadQueued = false;
				resolve();
			} else {
				that.pageLoadQueue.push(resolve);	
			}

		});
	},


	"run": function() {
		var 
			that = this, 
			i;

		// console.log("NOW WE RUN " + this.title);


		var p = this.initialize()
			.then(function() {
				return new Promise(function(resolve, reject) {
					// console.log("  ----] Preparing");
					that.prepare();
					// console.log("  ----] Setup steps");
					for(i = 0; i < that.steps.length; i++) {
						that.steps[i].setup(that.page);
					}
					// console.log("  ----] Done setting up steps");
					resolve();

				})
			})
			.then(function() { 
				// console.log("  ----] Start()");
				return that.start(); 
			})
			.then(function() { 
				// console.log("  ----] Execute steps()");
				return that.executeSteps(); 
			})
			.then(function() { 
				// console.log("  ----] ALL DONE IN THIS FLOW");
				// return that.executeSteps(); 
			});
			// .then(function() {that.waitforCompleted(); })

		return p;


	}
});



exports.BaseFlow = BaseFlow;
