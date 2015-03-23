"use strict";

var Promise = require('promise');

var Class = require('./Class').Class;


var Step = Class.extend({
	"init": function(title, funcs) {

		this.title = title;
		this.debug = false;
		this.html = false;

		this.funcs = {
			"evaluate": function(callback) {
				// console.log("Evaulation default behaviour");
				callback(true);
			},
			"execute": function(callback) {
				console.error("Undefined execution behaviour of this step.");
				callback();
			}
		};

		if  (funcs.debug) {
			this.debug = funcs.debug;
		}
		if  (funcs.html) {
			this.html = funcs.html;
		}

		if (funcs) {
			if (funcs.hasOwnProperty("evaluate") && typeof funcs.evaluate === 'function') {
				this.funcs.evaluate = funcs.evaluate;
			}
			if (funcs.hasOwnProperty("execute") && typeof funcs.execute === 'function') {
				this.funcs.execute = funcs.execute;
			}			
		}

	},
	"testCounter": function() {
		return this.testcounter;
	},
	"t": function(flow, txt) {
		return "[" + flow.title + " / " + this.title + "] " + txt;
	},
	"showDebug": function(flow, ctx) {
		var that = this;
		console.log("------- DEBUG -----  Flow [" + flow.title  + "] Step [" + that.title + "] ");
		console.log(" Page Title   : " + ctx.page.title);
		console.log(" Page URL     : " + ctx.page.url);
		if (this.html) {
			ctx.debugHTML();
		}
		console.log("------- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ");
	},


	"evaluate": function(callback) {
		this.funcs.evaluate.call(this, callback);	
	},

	"execute": function() {
		// console.log("   [EXECUTING] a step for " + this.title);
		var that = this;
		return that.funcs.execute.call(that)
			.then(function() {
				console.log (" ^ DONE with " + that.title);
				console.log();
			});

	},
	"setup": function(page) {
		this.page = page;
	}
});


exports.Step = Step;

