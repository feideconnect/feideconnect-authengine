"use strict";


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
		// console.log(" EVALUATE " + this.title );
		var that = this;
		describe("Evaluate " + this.title, function() {
			that.funcs.evaluate.call(that, callback);
		});
		
	},
	"execute": function(callback) {
		var that = this;
		describe("Execute " + this.title, function() {
			that.funcs.execute.call(that);
		});
	},
	"setup": function(flow, page) {
		var that = this;
		this.page = page;
		// this.casper.then(function() {
		// 	if (that.debug) {
		// 		that.showDebug(flow, this);
		// 	}
		// 	if (that.evaluate(this, flow)) {
		// 		// console.log("Flow [" + flow.title  + "] Step [" + that.title + "] EXECUTE");
		// 		that.execute(this);
		// 	} else {
		// 		// console.log("Flow [" + flow.title  + "] Step [" + that.title + "] SKIP");
		// 	}
		// });
	}
});


exports.Step = Step;

