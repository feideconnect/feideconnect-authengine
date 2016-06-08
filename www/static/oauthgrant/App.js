define(function(require, exports, module) {
	"use strict";

	var Class = require('../accountchooser/Class');

	// var FeideWriter = require('./FeideWriter');
	// var LocationController = require('./LocationController');
	var LanguageSelector = require('../accountchooser/LanguageSelector');
	var AccountStore = require('./AccountStore');

    var App = Class.extend({
    	"init": function() {
    		var that = this;
			
			this.accountstore = new AccountStore(visualTag);
			this.lang = new LanguageSelector($("#langselector"), true);

			$(".grantEntry").on("click", function(item) {
				// console.log("Click");
				$(item.currentTarget).toggleClass("grantEntryActive");
			});

			$("body").on("click", "#actAcceptBrVilk", function(item) {
				// console.log("Click");
				$('#myModal').modal('hide');
				$('#bruksvilkar').prop('checked', true);
				that.updateAcceptRequirement();
			});

			$("body").on("click", ".tglSimple", function(e) {
				e.preventDefault();
				$("body").toggleClass("simpleGrant");
			});

			$(".touOpen").on("click", function(e) {
				e.preventDefault(); e.stopPropagation();
				$('#myModal').modal('show');
			});

			if ($("body").hasClass("bypass")) {
				$("#submit").click();
				// console.error("Bypass simplegrant");
				// $("body").show();
			} else {
				$("#mcontent").show();
			}




			$("body").on("change", "#bruksvilkar", function(e) {
				// e.preventDefault();
				that.updateAcceptRequirement();

				// $("body").toggleCl
				// ass("simpleGrant");
			});

			this.updateAcceptRequirement();


			// Uncomment this to force "samtykkeerklæring" to show immediately. 
			// Used for debugging.
			// $('#myModal').modal('show');

			this.loadDictionary();

    	},

    	"updateAcceptRequirement": function() {


    		if ($("input#bruksvilkar").length === 1) {
	    		var val = $("input#bruksvilkar").is(":checked");
				if (val) {
					$(".reqAccept").removeAttr("disabled");
				} else {
					$(".reqAccept").attr("disabled", "disabled");
				}
    		}

			

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