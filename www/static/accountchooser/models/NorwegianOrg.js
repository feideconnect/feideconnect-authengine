define(function(require, exports, module) {
    "use strict";

    var
        Provider = require('./Provider'),
        Utils = require('../Utils')
        ;

    var NorwegianOrg = Provider.extend({
        "init": function(a, feideIdP) {
            a.country = 'no';

            // console.log("New Feide organization");
            // console.log(JSON.stringify(a, undefined, 2));

            if (a.uiinfo && a.uiinfo.geo) {
                a.geo = a.uiinfo.geo;
                delete a.uiinfo;
            }

            a.subid = a.id;
            a.id = feideIdP;

            this._super(a);
        },

        "isType": function(type) {
            if (!this.type) {
                return false;
            }
            for (var i = 0; i < this.type.length; i++) {
                if (this.type[i] === type) {
                    return true;
                }
            }
            return false;
        },
        "isEnabled": function() {
            if (!this.services) {
                return false;
            }
            for(var i = 0; i < this.services.length; i++) {
                if (this.services[i] === 'auth') {
                    return true;
                }
            }
            return false;
        },
        "getView": function() {
            var view = {
                id: this.id,
                subid: this.subid,
                classes: "",
                logo: "https://api.dataporten.no/orgs/fc:org:" + this.subid + "/logo",
                title: this.title,
                distance: this.distance,
                activeAccounts: this.activeAccounts,
                enforceLogout: this.enforceLogout,
            };
            if (this.activeAccounts) {
                view.classes = 'hasactive';
            }
            return view;
        }

    });

    return NorwegianOrg;

});
