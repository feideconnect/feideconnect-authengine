define(function(require, exports, module) {
	"use strict";

	var Class = require('../accountchooser/Class');

	// var FeideWriter = require('./FeideWriter');
	// var LocationController = require('./LocationController');

	var AccountStore = require('./AccountStore');

    var App = Class.extend({
    	"init": function() {
    		var that = this;
			
			this.accountstore = new AccountStore(visualTag);



			$(".grantEntry").on("click", function(item) {
				console.log("Click");
				$(item.currentTarget).toggleClass("grantEntryActive");
			});

			$("body").on("click", "#actAcceptBrVilk", function(item) {
				// console.log("Click");
				$('#myModal').modal('hide');
				$('#bruksvilkar').prop('checked', true);
			});

			$("body").on("click", ".tglSimple", function(e) {
				e.preventDefault();
				$("body").toggleClass("simpleGrant");
			});

			if ($("body").hasClass("bypass")) {
				$("#submit").click();
				console.error("Bypass simplegrant");
				// $("body").show();
			} else {
				$("body").show();
			}

    	}

    });
    return App;




});