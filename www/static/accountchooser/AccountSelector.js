define(function(require, exports, module) {
	"use strict";

	var Class = require('./Class');
	var DiscoveryController = require('./DiscoveryController');
	var AccountStore = require('../oauthgrant/AccountStore');


	var Utils = require('./Utils');



	var AccountSelector = Class.extend({

		"init": function(app, store) {
			var that = this;
			this.store = store;
			this.app = app;

			$("#accounts").on("click", ".accountentry", function(e) {
				e.preventDefault();
				var userid = $(e.currentTarget).data("userid");


				if ($(e.currentTarget).hasClass("disabled")) {
					return;
				}

				if  ($("#accounts").hasClass("modeRemove")) {
					// console.log("Ignoring, since in remove mode...");
					return;
				}
			
				that.app.disco.go(that.store.accts[userid]);
				// console.log("Selected to login using", userid, that.store.accts[userid]);
			});
			$("#accounts").on("click", ".actRemove", function(e) {
				e.preventDefault(); e.stopPropagation();
				var userid = $(e.currentTarget).closest('.accountentry').data("userid");
				that.store.removeAccountTag(userid);
				if (that.store.hasAny() ) {
					that.draw();    
				} else {
					$("#paneselector").hide();
					that.app.disco.activate();
				}
				
			
				// that.app.disco.go(that.store.accts[userid]);
				// console.log("About to remove", userid);
			});


			$("#accounts").on("click", "#removeacct", function(e) {
				e.preventDefault();
				$("#accounts").addClass("modeRemove");
			});
			$("#accounts").on("click", "#removedone", function(e) {
				e.preventDefault();
				$("#accounts").removeClass("modeRemove");
			});


			$("body").on("click", "#altlogin", function(e) {
				e.preventDefault();
				 $("#paneselector").hide();
				that.app.disco.activate();
			});

		},

		"activate": function() {
			this.draw();
			$("#paneselector").show();
		},
		

		"matchOneDefType": function(accepteddef, accountdef) {
			for(var i = 0; i < accepteddef.length; i++) {

				// console.error("  >>>>  CHECK if " + accepteddef[i] + ' matches ' + accountdef[i] );

				if (accepteddef[i] === 'all') {
					return true;

				} else if (i > (accountdef.length-1)) {

					return false;

				} else if (accepteddef[i] !== accountdef[i]) {
					return false;
				}

			}
			return true;
		},


		"matchType": function(accountdef) {

			var accepteddefs = this.app.getAuthProviderDef();
			for (var i = 0; i < accepteddefs.length; i++) {

				var x = this.matchOneDefType(accepteddefs[i], accountdef);
				if (x) { return true; }

			}
			return false;
		},




		"matchAnyType": function(types) {			
			var accepteddefs = this.app.getAuthProviderDef();
			// console.error("  ›  CHECK if \n" + JSON.stringify(types) + ' does match the legal ' + "\n" + JSON.stringify(accepteddefs));
			for (var i = 0; i < types.length; i++) {
				var x = this.matchType(types[i]);
				if (x) {return true; }
			}
			return false;
		},


		"hasSameUserID": function(a, b) {
			if (!a.userids) { 
				return false;
			}
			if (!b.userids) {
				return false;
			}
			for (var i = 0; i < a.userids.length; i++) {
				for (var j = 0; j < b.userids.length; j++) {
					if (a.userids[i] === b.userids[j]) {
						return true;
					}
				}
			}
			return false;
		},

		"isActiveAccount": function(a) {
			if (!window.activeAccounts) {
				return false;
			}
			for (var i = 0; i < window.activeAccounts.length; i++) {
				var x = window.activeAccounts[i];
				if (this.hasSameUserID(a, x)) {
					return true;
				}
			}
			return false;
		},


		"draw": function() {
			var txt = '';

			var def = this.app.getAuthProviderDef();
			var allowed;

			for(var userid in this.store.accts) {

				var a = this.store.accts[userid];

				allowed = true;
				if (a.hasOwnProperty('def')) {
					// console.error("accounts draw", a);
					allowed = this.matchAnyType(a.def);
					// console.error("Is this account ok?\n" + JSON.stringify(a.def) + "\nWhat is legal is :\n" + JSON.stringify( def));
					// console.error("Check match any type", allowed);
				}
				var classes = ['list-group-item', 'accountentry'];
				if (!allowed) { classes.push('disabled'); }

				var isActive = this.isActiveAccount(a);

				// console.log("Processing", a);
				// console.log("Active accounts", window.activeAccounts);

				txt += '<a href="#" class="' + classes.join(' ') + '" data-userid="' + Utils.quoteattr(userid) + '" style="">' +
					'<div class="media"><div class="media-left media-middle">' + 
							'<img class="media-object" style="width: 64px; height: 64px" src="' + Utils.quoteattr(a.photo) + '" alt="...">' + 
						'</div>' +
						'<div class="media-body">' + 
							'<p class="showOnRemove" style=""><button class="btn btn-danger actRemove" style="float: right">' + this.app.dictionary.remove + '</button></p>' + 

							'<i style="float: right; margin-top: 20px" class="fa fa-chevron-right fa-2x hideOnRemove"></i>' +
							(isActive ? '<i style="color: #6a6; float: right; margin-top: 20px; margin-right: 12px" class="fa fa-circle fa-2x"></i>' : '') +
							'<p style="font-size: 140%; margin: 0px">' + Utils.quoteattr(a.name) + '</p>' + 
							'<p style="font-size: 100%; margin: 0px; margin-top: -6px">' + Utils.quoteattr(a.title) + '</p>' + 
							'<p style="font-size: 70%; color: #aaa; margin: 0px">' + Utils.quoteattr(userid) + '</p>' + 
						'</div>' +
					'</div>' +
				'</a>';
			}

			txt += '<div class="list-group-item">' + 
				'<p style="text-align: right; font-size: 80%; marging-top: 2em">' +
					'   <a id="removeacct" class="hideOnRemove" href="" style="color: #888; "><i class="fa fa-times"></i>' + this.app.dictionary.removeacct + ' </a>' +
					'   <a class="showOnRemove" id="removedone" href="" style="color: #888"><i class="fa fa-check"></i> ' + this.app.dictionary.done + '</a>' + 
				'</p>' +
				'<p style="text-align: center; marging-top: 1em"><a id="altlogin" href="">' + this.app.dictionary.oranotheraccount + '</a></p>' +
				'</div>';

			$("#accounts").empty().append(txt);

		}

	});
	return AccountSelector;




});