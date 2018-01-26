const Provider = require('./Provider');
const Utils = require('../Utils');

class NorwegianOrg extends Provider {
    constructor(a, feideIdP) {
        a.country = 'no';

        // console.log("New Feide organization");
        // console.log(JSON.stringify(a, undefined, 2));

        if (a.uiinfo && a.uiinfo.geo) {
            a.geo = a.uiinfo.geo;
            delete a.uiinfo;
        }

        a.subid = a.id;
        a.id = feideIdP;
        super(a);
    }

    isType(type) {
        if (!this.type) {
            return false;
        }
        for (var i = 0; i < this.type.length; i++) {
            if (this.type[i] === type) {
                return true;
            }
        }
        return false;
    }
    isEnabled() {
        if (!this.services) {
            return false;
        }
        for(var i = 0; i < this.services.length; i++) {
            if (this.services[i] === 'auth') {
                return true;
            }
        }
        return false;
    }
    getView() {
        var view = {
            id: this.id,
            subid: this.subid,
            type: "saml",
            classes: "",
            logo: "https://api.dataporten.no/orgs/fc:org:" + this.subid + "/logo",
            title: this.title,
            distance: this.distance,
            activeAccounts: this.activeAccounts,
            enforceLogout: this.enforceLogout,
            logout: true,
            showActive: !!this.activeAccounts
        };
        if (this.hasOwnProperty("logout")) {
            view.logout = this.logout;
        }
        if (this.activeAccounts) {
            view.classes = 'hasactive';
        }
        return view;
    }

};

module.exports = NorwegianOrg;
