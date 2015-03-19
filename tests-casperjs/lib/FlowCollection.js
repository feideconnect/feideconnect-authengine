"use strict";

var system = require('system');
var Class = require('./Class').Class;

var BaseOAuthFlow = require('./flows/BaseOAuthFlow').BaseOAuthFlow;
var BadRedirectURI = require('./flows/BadRedirectURI').BadRedirectURI;


var FlowCollection = Class.extend({
	"init": function(casper, oauth) {
		this.casper = casper;
		this.oauth = oauth;
		this.flows = [];
		this.flows.push(new BaseOAuthFlow(casper, oauth));
		this.flows.push(new BadRedirectURI(casper, oauth));

	},
	"run": function() {
		var i;
		for(i = 0; i < this.flows.length; i++) {
			this.flows[i].run();
		}
	}
});


exports.FlowCollection = FlowCollection;

