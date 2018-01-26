const $ = require('jquery');
const DiscoveryController = require('./DiscoveryController');
const AccountStore = require('../oauthgrant/AccountStore');
const Utils = require('./Utils');
const AccountListView = require('./views/AccountListView');

class AccountSelector {

    constructor(app, store, accounts) {
        var that = this;
        this.store = store;
        this.app = app;
        this.activeAccounts = accounts;
        this.accountListView = new AccountListView(app);

        $("#accounts").on("click", ".accountentry", function(e) {
            e.preventDefault();
            var userid = $(e.currentTarget).data("userid");

            if ($(e.currentTarget).hasClass("disabled")) {
                return;
            }

            if ($("#accounts").hasClass("modeRemove")) {
                // console.log("Ignoring, since in remove mode...");
                return;
            }

            // console.log("Selected to login using", userid, that.store.accts[userid]);
            that.app.disco.go(that.store.accts[userid]);
        });
        $("#accounts").on("click", ".actRemove", function(e) {
            e.preventDefault();
            e.stopPropagation();
            var userid = $(e.currentTarget).closest('.accountentry').data("userid");
            // console.log("About to remove", userid);
            that.store.removeAccountTag(userid);
            if (that.store.hasAny()) {
                that.draw();
            } else {
                $("#paneselector").hide();
                that.app.disco.activate();
            }
        });


        $("#removeacct").on("click", function(e) {
            e.preventDefault();
            $("#accounts").addClass("modeRemove");
            $('#removedone').show();
            $(this).hide();
        });
        $("#removedone").on("click", function(e) {
            e.preventDefault();
            $("#accounts").removeClass("modeRemove");
            $('#removeacct').show();
            $(this).hide();
        });


        $("body").on("click", "#altlogin", function(e) {
            e.preventDefault();
            $("#paneselector").hide();
            that.app.disco.activate();
        });

    }

    activate() {
        this.draw();
        $("#paneselector").show();
        $('#page-title').text(this.app.dictionary['chooseaccount']);
    }


    matchOneDefType(accepteddef, accountdef) {
        for (var i = 0; i < accepteddef.length; i++) {

            // console.error("  >>>>  CHECK if " + accepteddef[i] + ' matches ' + accountdef[i] );

            if (accepteddef[i] === 'all') {
                return true;

            } else if (i > (accountdef.length - 1)) {

                return false;

            } else if (accepteddef[i] !== accountdef[i]) {
                return false;
            }

        }
        return true;
    }


    matchType(accountdef) {

        var accepteddefs = this.app.getAuthProviderDef();
        for (var i = 0; i < accepteddefs.length; i++) {

            var x = this.matchOneDefType(accepteddefs[i], accountdef);
            if (x) {
                return true;
            }

        }
        return false;
    }



    matchAnyType(types) {
        var accepteddefs = this.app.getAuthProviderDef();
        // console.error("  ›  CHECK if \n" + JSON.stringify(types) + ' does match the legal ' + "\n" + JSON.stringify(accepteddefs));
        for (var i = 0; i < types.length; i++) {
            var x = this.matchType(types[i]);
            if (x) {
                return true;
            }
        }
        return false;
    }


    hasSameUserID(a, b) {
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
    }

    isActiveAccount(a) {
        if (!this.activeAccounts) {
            return false;
        }
        for (var i = 0; i < this.activeAccounts.length; i++) {
            var x = this.activeAccounts[i];
            if (this.hasSameUserID(a, x)) {
                return true;
            }
        }
        return false;
    }

    hasAnyActive() {
        var def = this.app.getAuthProviderDef(),
            allowed,
            anyAllowed = false;

        for (var userid in this.store.accts) {
            var a = this.store.accts[userid];
            allowed = true;
            if (a.hasOwnProperty('def')) {
                allowed = this.matchAnyType(a.def);
            }
            if (allowed) {
                anyAllowed = true;
            }
        }
        return anyAllowed;
    }

    draw() {
        var txt = '';
        var def = this.app.getAuthProviderDef();
        var allowed;

        var data = {
            dict: this.app.dictionary,
            accounts: []
        }

        for (var userid in this.store.accts) {

            var a = this.store.accts[userid];
            allowed = true;
            if (a.hasOwnProperty('def')) {
                // console.error("accounts draw", a);
                allowed = this.matchAnyType(a.def);
                // console.error("Is this account ok?\n" + JSON.stringify(a.def) + "\nWhat is legal is :\n" + JSON.stringify( def));
                // console.error("Check match any type", allowed);
            }
            var classes = ['list-group-item', 'accountentry'];
            if (!allowed) {
                classes.push('disabled');
            }

            var dataEntry = $.extend({}, a);
            dataEntry.isActive = this.isActiveAccount(a);
            dataEntry.classes = classes.join(' ');
            dataEntry.userid = userid;
            data.accounts.push(dataEntry);

        }

        this.accountListView.update(data)
            .then(function(html) {
                // console.log("output html is ", html)
                $("#accounts").empty().append(html);
            })
            .catch(function(err) {
                console.error("Error processing template for accountListView", err);
            })

        // $("#accounts").empty().append(txt);

    }
};

module.exports = AccountSelector;
