define(function(require, exports, module) {
    "use strict";

    var Class = require('./Class');
    var DiscoveryController = require('./DiscoveryController');
    var AccountStore = require('../oauthgrant/AccountStore');
    var AccountSelector = require('./AccountSelector');
    var LanguageSelector = require('./LanguageSelector');
    var Controller = require('./Controller');
    var Utils = require('./Utils');

    /*
     *  This is the main App controlling the acountchooser and discovery
     *
     * It loads two panes, one with the accountchooser and one with the discovery and lets the user switch between them.
     * It activates the chooser if there exists some stored accounts
     */
    var App = Controller.extend({
        "init": function() {

            this.dictionary = window.dictionary;
            this.config = window.config;
            this.client = window.client;
            if (!this.config) {
                console.error("Could not get configuration. Was missing from mustache tempalte by a mistake.");
            }
            /* this.lang = new LanguageSelector($("#langselector"), true);
             * this.lang.setConfig(this.config);
             * this.lang.initLoad(this.dictionary._lang);*/

            if (this.client) {
                this.drawClientInfo();
            }

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
                    // console.log("HAS ANY ACTIVE?", that.selector.hasAnyActive() );
                    // return that.disco.activate();
                    if (that.selector.hasAnyActive())  {
                        that.selector.activate();
                    } else {
                        that.disco.activate();
                    }
                })
                .then(function() {
                    that._initLoaded();
                })
                .then(undefined, function(err) {
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

        "getClientsURL": function(url) {
            return this.config.endpoints.clientadm + url;
        },

        "drawClientInfo": function() {
            var logourl = this.getClientsURL('/clients/' + this.client.id + '/logo');
            $(".clientinfo").show();
            $(".clientname").text(this.client.name);
            $(".clientlogo").empty().append('<img style="max-height: 64px; max-width: 64px" src="' + logourl + '" />');
        }

    });
    return App;


});
