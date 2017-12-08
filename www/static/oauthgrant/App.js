define(function(require, exports, module) {
    "use strict";

    var vex = require('vex');
    var Class = require('accountchooser/Class');
    var LanguageSelector = require('accountchooser/LanguageSelector');
    var AccountStore = require('./AccountStore');

    var App = Class.extend({
        "init": function(page) {
            if (page !== 'oauthgrant') {
                return;
            }
            var that = this;
            var containerUserTerms = $('#container-user-terms');
            var containerServiceConsent = $('#container-service-consent');

            this.accountstore = new AccountStore(visualTag);
            this.lang = new LanguageSelector($("#langselector"), true);

            $(".grantEntry").on("click", function(item) {
                // console.log("Click");
                $(item.currentTarget).toggleClass("grantEntryActive");
            });

            $("body").on("click", ".accept-user-terms", function() {
                containerUserTerms.addClass('hide');
                containerServiceConsent.removeClass('hide');
            });

            $("body").on("click", ".tglSimple", function(e) {
                e.preventDefault();
                $("body").toggleClass("simpleGrant");
            });

            $(".touOpen").on("click", function(e) {
                e.preventDefault();
                vex.defaultOptions.className = 'vex-theme-os';
                var policyInfo = vex.open({
                    unsafeContent: $('#privacy-policy').html()
                });
            });

            $("body").on("click", ".touClose", function(e) {
                e.preventDefault();
                vex.closeAll();
            });

            if ($("body").hasClass("bypass")) {
                $("#submit").click();
                // console.error("Bypass simplegrant");
                // $("body").show();
            } else {
                $("#mcontent").show();
            }


            // Uncomment this to force "samtykkeerklæring" to show immediately.
            // Used for debugging.
            // $('#myModal').modal('show');

            this.loadConfig().then(function() {
                that.loadDictionary();
            });

        },

        "loadConfig": function() {
            var that = this;

            return new Promise(function(resolve, reject) {

                // console.error("About to load config");
                $.getJSON('/accountchooser/config',function(data) {
                    that.lang.setConfig(data);
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
