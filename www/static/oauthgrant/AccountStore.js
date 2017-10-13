define(function(require, exports, module) {
    "use strict";

    var Class = require('../accountchooser/Class');


    var AccountStore = Class.extend({
        "init": function(visualTag) {
            var that = this;
            this.visualTag = null;
            this.accts = {};

            this.loadAccounts();

            if (visualTag) {
                this.visualTag = visualTag;
                    // console.log("AccountStore Visual tag received...");
                    // console.error(visualTag);

                    if (visualTag.rememberme) {
                        this.saveAccountTag(visualTag);
                    } else {
                        console.log("Visual account tag is not saved, because user did not select so.");
                    }
            }

            // console.log("Accounts", this.accts);

        },

        "hasAny": function() {
            for(var key in this.accts) {
                if (this.accts.hasOwnProperty(key)) {
                    return true;
                }
            }
            return false;
        },

        "loadAccounts": function() {
            var c = localStorage.getItem("accounts");
            if (c) {
                var cd = JSON.parse(c);
                this.accts = cd;
            }

        },

        "removeAccountTag": function(userid) {
            if (this.accts[userid]) {
                delete this.accts[userid];
                localStorage.setItem("accounts", JSON.stringify(this.accts));
            }
        },

        "saveAccountTag": function(vt) {

            var userid = vt.userids[0];

            if (this.accts[userid]) {
                console.log("Visual account tag for " + userid + " is already stored. Updating...");
            }
            this.accts[userid] = vt;

                try {
                    localStorage.setItem("accounts", JSON.stringify(this.accts));
                } catch (error) {
                    console.error("Error saving account", error);
                }


        }

    });
    window.store = new AccountStore();
    return AccountStore;




});
