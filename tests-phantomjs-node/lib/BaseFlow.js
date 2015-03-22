var phantom = require('phantom');
var assert = require("assert")
var Class = require('./Class').Class;

// var Promise = require('promise');



var BaseFlow = Class.extend({
	"init": function(ph, starturl) {
		this.ph = ph;
		this.starturl = starturl;
		this.title = 'Base flow';
		this.steps = [];
		this.pointer = 0;

		this.page = null;

	},
	"initialize": function(pdone) {
		var that = this;
		this.ph.createPage(function(err, page) {
			console.log("Preparing with a new page for " + that.title );
			that.page = page;

			that.page.onUrlChanged = function(url) {
				console.log('URL change [' + url + ']');
			}
			that.page.onConsoleMessage = function(msg) {
				console.log('[' + that.current + '] ›' + msg);
			};

			that.page.onLoadFinished = (function(x) {
				return function(status) {
					console.log("Loaded finnished. ");
					x.proceed();
				};
			})(that);

			pdone();
		});
		
	},

	"prepare": function() {

	},

	"start": function(sdone) {
		
		var that = this;
		this.page.open(this.starturl, function(status) {

			console.log("Loaded status for [" + that.starturl + "] " + status);
			// console.log("URL IS " + that.page.url);
			// console.log(that.page);
			// console.log('Content: ' + that.page.getContent());
			sdone();
		});
	},

	"proceed": function() {
		console.log("We are now proceeding... " + this.pointer + " of " + this.steps.length);
		var that = this;
		++this.pointer;

		if (this.pointer > this.steps.length) {
			return this.completed();
		}

		var cur = this.steps[this.pointer-1];

		cur.evaluate(function(evaluated) {

			if (evaluated) {
				cur.execute(function() {
					that.proceed();
				});
			} else {
				that.proceed();
			}

		});

	},

	"completed": function() {
		console.log("We are now complete. Have no more steps prepared.");
		if (this.onCompletedCallback && typeof this.onCompletedCallback === 'function') {
			this.onCompletedCallback();
		}
	},

	"onCompleted": function(callback) {
		this.onCompletedCallback = callback;
	},

	"run": function() {
		var 
			that = this, 
			i;


		this.prepare();

		this.initialize(function() {

			for(i = 0; i < that.steps.length; i++) {
				that.steps[i].setup(that, that.page);
			}

			that.start(function() {
				console.log("Now we are ready to do the steps...");
				// that.proceed();
			});
		});


		// this.casper.test.begin(this.title, testcounter, function(test) {
		// 	var i;
		// 	that.start();


		// 	that.casper.run(function() {
		// 		test.done();
		// 	});

		// });

	}
});



exports.BaseFlow = BaseFlow;
