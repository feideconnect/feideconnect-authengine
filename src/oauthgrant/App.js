const vex = require('vex-js');
const LanguageSelector = require('../accountchooser/LanguageSelector');
const AccountStore = require('./AccountStore');

class App {
    constructor() {
        var that = this;
        var containerUserTerms = $('#container-user-terms');
        var containerServiceConsent = $('#container-service-consent');
        var containerServiceDetails = $('#container-service-details');

        this.accountstore = new AccountStore(visualTag);
        this.lang = new LanguageSelector($("#langselector"), true);

        /* Listeners for the privacy policy dialog */

        // Open privacy policy dialog
        $(".touOpen").on("click", function(e) {
            e.preventDefault();
            vex.defaultOptions.className = 'vex-theme-os';
            var policyInfo = vex.open({
                unsafeContent: $('#privacy-policy').html()
            });
        });

        // Close privacy policy dialog
        $("body").on("click", ".touClose", function(e) {
            e.preventDefault();
            vex.closeAll();
        });


        /* Listener for expanding more information about the service */
        $(".more-information").on("click", function(e) {
            e.preventDefault();
            var $button = $(this);
            containerServiceDetails.slideToggle(function() {
                $button.toggleClass('more-information less-information');
            });
        });



        $("body").on("click", ".accept-user-terms", function() {
            containerUserTerms.addClass('hide');
            containerServiceConsent.removeClass('hide');
        });

        $("body").on("click", ".tglSimple", function(e) {
            e.preventDefault();
            $("body").toggleClass("simpleGrant");
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

    }

    loadConfig() {
        var that = this;

        return new Promise(function(resolve, reject) {

            // console.error("About to load config");
            $.getJSON('/accountchooser/config',function(data) {
                that.lang.setConfig(data);
                resolve();
            });

        });

    }

    loadDictionary() {
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
};

module.exports = App;
