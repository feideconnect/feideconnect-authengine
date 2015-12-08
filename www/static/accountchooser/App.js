define(function(require, exports, module) {
	"use strict";

	var Class = require('./Class');
	var DiscoveryController = require('./DiscoveryController');
	var AccountStore = require('../oauthgrant/AccountStore');
	var AccountSelector = require('./AccountSelector');
	var LanguageSelector = require('./LanguageSelector');
	var Controller = require('./Controller');

	var Utils = require('./Utils');



	var App = Controller.extend({
		"init": function() {
			var that = this;
			
			that.config = null;

			this.client = null;

			this.lang = new LanguageSelector($("#langselector"), true);
			this.disco = new DiscoveryController(this);
			this.accountstore = new AccountStore();
			this.selector = new AccountSelector(this, this.accountstore);

			this.parseRequest();
			this._super(undefined, true);            

		},

		"initLoad": function() {

			var that = this;
			return Promise.resolve()
				.then(function() {
					return that.loadConfig();
				})
				.then(function() {
					return Promise.all([
						that.loadClientInfo(),
						that.loadDictionary()
					]);
				})
				.then(function() {
					if (that.accountstore.hasAny())  {
						that.selector.activate();
					} else {
						that.disco.activate();
					}
				})
				.then(that.proxy("_initLoaded"))
				.catch(function(err) {
					console.error("Error loading AccountChooser", err);
					that.setErrorMessage("Error loading AccountChooser", "danger", err);
				});

		},

		"setErrorMessage": function(title, type, msg) {

			var that = this;
			type = (type ? type : "danger");

			var pmsg = '';
			if (typeof msg === 'object' && msg.hasOwnProperty("message")) {
				pmsg = '<p>' + Utils.quoteattr(msg.message, false).replace("\n", "<br />") + '</p>';
			} else if (typeof msg === 'string') {
				pmsg = '<p>' + Utils.quoteattr(msg, false).replace("\n", "<br />") + '</p>';
			}

			var str = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' +
				' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
				(title ? '<strong>' + Utils.quoteattr(title, false).replace("\n", "<br />") + '</strong>' : '') +
				pmsg +
				'</div>';

			if (this.hasOwnProperty("errorClearCallback")) {
				clearTimeout(this.errorClearCallback);
			}

			this.errorClearCallback = setTimeout(function() {
				$("#errorcontainer").empty();
			}, 10000);

			$("#errorcontainer").empty().append(str);

		},


    	"getAuthProviderDef": function() {

    		var p, pp;
    		if (this.authproviders) {
    			return this.authproviders;
    		}
    		this.authproviders = [];

    		if (this.client) {

	    		if (this.client.authproviders && this.client.authproviders !== null) {

	    			p = this.client.authproviders;
	    			// console.log("P is ", p);
		    		for(var i = 0; i < p.length; i++) {
		    			pp = p[i].split('|');
		    			this.authproviders.push(pp);
		    		}

		    	} else {
		    		this.authproviders.push(['all']);
		    	}
		    }
		    
	    	if (this.authproviders.length === 0) {
	    		this.authproviders.push(['all']);
	    	}
    		return this.authproviders;
    	},


		"parseRequest": function() {
			if (acrequest) {
				this.request = acrequest;
			}
		},

		"getCoreURL": function(url) {
			return this.config.endpoints.core + url;
		},

		"loadClientInfo": function() {
			var that = this;
			// console.log("Loading client info", this.request);

			// console.error("Draw");
			// 
			if (!this.request.clientid) {
				return Promise.resolve();
			}

			var UUIDregex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

			if (!UUIDregex.test(this.request.clientid)) {
				console.error("Invalid Client ID. (Must be an  UUID format)");
				throw new Error("Invalid Client ID. (Must be an  UUID format)");
			}

			return new Promise(function(resolve, reject) {
				
				// console.error("About to load config");
				var url = that.getCoreURL('/clientadm/clients/') + that.request.clientid;
				// console.log("Contacting url", url);
				$.getJSON(url,function(data) {
					// data.authproviders = [];
					// data.authproviders.push('all');
					// data.authproviders.push('social|all');
					// data.authproviders.push('social|facebook');
					// data.authproviders.push('social|twitter');
					// data.authproviders.push('social|linkedin');
					// data.authproviders.push('other|all');
					// data.authproviders.push('other|idporten');
					// data.authproviders.push('other|openidp');
					// // data.authproviders.push('other|openidp');
					// data.authproviders.push('other|feidetest');
					// data.authproviders.push('feide|all');
					// data.authproviders.push('feide|go');
					// data.authproviders.push('feide|he');
					// data.authproviders.push('feide|realm|uninett.no');
					// data.authproviders.push('feide|realm|iktsenteret.no');

					// console.error("Got clientinfo data:", data);
					that.client = data;
					that.drawClientInfo();

					// console.log("Loading client info", this.request);
					resolve();
				});

			});
		},

		"drawClientInfo": function() {
			// console.error("Draw");
			var logourl = this.getCoreURL('/clientadm/clients/' + this.client.id + '/logo');

			$(".clientinfo").show();
			$(".clientname").empty().append(this.client.name);
			$(".clientlogo").empty().append('<img style="max-height: 64px; max-width: 64px" src="' + logourl + '" />');
		},

		"loadConfig": function() {
			var that = this;

			return new Promise(function(resolve, reject) {
				
				// console.error("About to load config");
				$.getJSON('/accountchooser/config',function(data) {
					that.config = data;
					// console.error("Config was loaded", that.config);
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
					// console.error("Dictionary was loaded", that.dictionary);
					// that.initAfterLoad();
					that.lang.initLoad(data._lang);
					resolve();
				});

			});

		}


	});
	return App;


});