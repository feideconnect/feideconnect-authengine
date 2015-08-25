define(function(require, exports, module) {
	"use strict";

	var Class = require('./Class');
	var DiscoveryController = require('./DiscoveryController');
	var AccountStore = require('../oauthgrant/AccountStore');
	var AccountSelector = require('./AccountSelector');

    var Controller = require('./Controller');

    var App = Controller.extend({
    	"init": function() {
    		var that = this;
    		
            that.config = null;

            this._super(undefined, true);
            
    		this.disco = new DiscoveryController(this);
    		this.accountstore = new AccountStore();
    		this.selector = new AccountSelector(this, this.accountstore);

    	},

        "initLoad": function() {

            var that = this;
            return Promise.resolve()
                .then(function() {

                    // console.error("App is loading...")
                    
                })
                .then(function() {
                    return Promise.all([
                        that.loadDictionary(),
                        that.loadConfig()
                    ]);
                })
                .then(function() {


                    // console.error("App is completed loading..");

                    if (that.accountstore.hasAny())  {
                        that.selector.activate();
                    } else {
                        that.disco.activate();
                    }
                    // console.error("App is completed (2)");
    
                })
                .then(that.proxy("_initLoaded"));

        },

        "loadConfig": function() {
            var that = this;

            return new Promise(function(resolve, reject) {
                
                // console.error("About to load config");
                $.getJSON('/accountchooser/config',function(data) {
                    that.config = data;
                    // console.error("Config was loaded");
                    // that.initAfterLoad();
                    resolve();
                });

            });

        },


        "loadDictionary": function() {
            var that = this;

            return new Promise(function(resolve, reject) {
                
                // console.error("About to load dictionary");
                $.getJSON('/dictionary',function(data) {
                    that.dictionary = data;
                    // console.error("Dictionary was loaded");
                    // that.initAfterLoad();
                    resolve();
                });

            });

        }


    });
    return App;


});