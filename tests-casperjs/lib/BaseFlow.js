"use strict";

var system = require('system');
var Class = require('./Class').Class;



var BaseFlow = Class.extend({
	"init": function(casper, starturl) {
		this.casper = casper;
		this.starturl = starturl;
		this.title = 'Base flow';
		this.steps = [];
	},
	"prepare": function() {
	},
	"start": function() {
		this.casper.start(this.starturl);
	},
	"run": function() {
		var 
			that = this, 
			i;

		this.prepare();

		var testcounter = 0;
		for(i = 0; i < that.steps.length; i++) {
			testcounter += that.steps[i].testCounter();
		}

		this.casper.test.begin(this.title, testcounter, function(test) {
			var i;
			that.start();

			for(i = 0; i < that.steps.length; i++) {
				that.steps[i].setup(that, test);
			}
			that.casper.run(function() {
				test.done();
			});

		});

	}
});



exports.BaseFlow = BaseFlow;
