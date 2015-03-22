"use strict";


var Class = require('./Class').Class;

// var BaseFlow = require('./BaseFlow').BaseFlow;
var BaseOAuthFlow = require('./flows/BaseOAuthFlow').BaseOAuthFlow;
// var BadRedirectURI = require('./flows/BadRedirectURI').BadRedirectURI;


var FlowCollection = Class.extend({
	"init": function(ph, oauth) {
		this.ph = ph;
		this.oauth = oauth;
		this.flows = [];
		// this.flows.push(new BaseFlow(ph, "http://uninett.no"));
		this.flows.push(new BaseOAuthFlow(ph, oauth));
		// this.flows.push(new BadRedirectURI(oauth));

	},
	"run": function() {
		var i, that = this;
		for(i = 0; i < this.flows.length; i++) {
			this.flows[i].run();
			this.flows[i].onCompleted((function() {
				// that.ph.exit();
				console.log(" -- - - - - - - -  DONE");
			}));
		}
	}
});


exports.FlowCollection = FlowCollection;

