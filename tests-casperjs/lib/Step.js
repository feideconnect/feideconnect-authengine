"use strict";

var system = require('system');
var Class = require('./Class').Class;

var Step = Class.extend({
	"init": function(casper, title, testcounter, funcs) {
		this.casper = casper;
		this.title = title;
		this.debug = false;
		this.html = false;
		this.testcounter = testcounter;

		this.funcs = {
			"evaluate": function() {
				// console.log("Evaulation default behaviour");
				return true;
			},
			"execute": function() {
				console.error("Undefined execution behaviour of this step.");
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
	"evaluate": function(ctx, flow) {
		var res = this.funcs.evaluate.call(this, ctx, this.test);
		if (!res) {
			this.test.skip(this.testcounter, this.t(flow, "Skip this step"));
		}
		return res;
	},
	"execute": function(ctx) {
		return this.funcs.execute.call(this, ctx, this.test);
	},
	"setup": function(flow, test) {
		var that = this;
		this.test = test;
		this.casper.then(function() {
			if (that.debug) {
				that.showDebug(flow, this);
			}
			if (that.evaluate(this, flow)) {
				// console.log("Flow [" + flow.title  + "] Step [" + that.title + "] EXECUTE");
				that.execute(this);
			} else {
				// console.log("Flow [" + flow.title  + "] Step [" + that.title + "] SKIP");
			}
		});
	}
});


exports.Step = Step;

