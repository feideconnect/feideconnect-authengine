define(function(require, exports, module) {
	"use strict";

	var Class = require('./Class');
	var DiscoveryController = require('./DiscoveryController');
	var AccountStore = require('../oauthgrant/AccountStore');
	var AccountSelector = require('./AccountSelector');



    var App = Class.extend({
    	"init": function() {
    		var that = this;
    		
    		this.disco = new DiscoveryController();
    		this.accountstore = new AccountStore();
    		this.selector = new AccountSelector(this, this.accountstore);	

    		if (this.accountstore.hasAny())  {
    			this.selector.activate();
    			
    		} else {
    			this.disco.activate();

    		}
    		
    	}
    });
    return App;


});