const $ = require('jquery');
const DiscoveryController = require('./DiscoveryController');
const AccountStore = require('../oauthgrant/AccountStore');
const AccountSelector = require('./AccountSelector');
const LanguageSelector = require('./LanguageSelector');
const Controller = require('./Controller');
const Utils = require('./Utils');

/*
 *  This is the main App controlling the acountchooser and discovery
 *
 * It loads two panes, one with the accountchooser and one with the discovery and lets the user switch between them.
 * It activates the chooser if there exists some stored accounts
 */
class App extends Controller {
    constructor() {
        super(undefined, true);

        const data = $('#app-data');
        console.log(data);
        this.dictionary = data.data('dictionary');
        this.config = data.data('config');
        this.client = data.data('client');
        this.request = data.data('acrequest');
        this.activeAccounts = data.data('accounts');
        this.location = data.data('loc');
        if (!this.config) {
            console.error("Could not get configuration. Was missing from mustache tempalte by a mistake.");
        }

        console.log(this.config);

        /* this.lang = new LanguageSelector($("#langselector"), true);
         * this.lang.setConfig(this.config);
         * this.lang.initLoad(this.dictionary._lang);*/

        if (this.client) {
            this.drawClientInfo();
        }

        this.disco = new DiscoveryController(this, this.request, this.activeAccounts, this.loc);
        this.accountstore = new AccountStore();
        this.selector = new AccountSelector(this, this.accountstore, this.activeAccounts);

    }

    initLoad() {

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

    }

    setErrorMessage(title, type, msg) {

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

    }


    getAuthProviderDef() {

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
    }

    getClientsURL(url) {
        return this.config.endpoints.clientadm + url;
    }

    drawClientInfo() {
        var logourl = this.getClientsURL('/clients/' + this.client.id + '/logo');
        $(".clientinfo").show();
        $(".clientname").text(this.client.name);
        $(".clientlogo").empty().append('<img src="' + logourl + '" />');
    }
};

module.exports = App;
