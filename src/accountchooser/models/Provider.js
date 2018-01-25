define(function(require, exports, module) {
    "use strict";

    var
        Model = require('./Model'),
        Utils = require('../Utils')
        ;

    var Provider = Model.extend({

        "init": function(a) {
            // console.log("New SAML provider");
            // console.log(JSON.stringify(a, undefined, 2));
            this._super(a);
        },

        "getDistance": function(loc) {

            // We cache the distance, but only use the cache when we are sure that the distanceFrom is the same.
            var cacheStr = (loc.lat ? (loc.lat + ',' + loc.lon) : 'na');
            if (this.distance && this.distanceFrom && this.distanceFrom === cacheStr) {
                return this.distance;
            }

            // We calculate the distance only when the entity has a geo coordinate associated.
            if (loc && this.hasOwnProperty("geo") && this.geo.hasOwnProperty("lat") && this.geo.hasOwnProperty("lon")) {
                this.distance = Utils.calculateDistance(loc.lat, loc.lon, this.geo.lat, this.geo.lon);
            } else {
                this.distance = 9999;
            }

            var minDistance = 9999;
            if (this.hasOwnProperty("geo") && this.geo.length) {
                for (var i = 0; i < this.geo.length; i++) {
                    var geo = this.geo[i];
                    var dist = Utils.calculateDistance(loc.lat, loc.lon, geo.lat, geo.lon);
                    if (dist < minDistance) {
                        minDistance = dist;
                    }
                }
            }
            this.distance = minDistance;
            this.distanceFrom = cacheStr;
            return this.distance;
        },

        "matchesActiveAccount": function(a) {
            return false;
        },

        "matchType": function(type) {

            // console.error("MATCH TYPE", type, this.def);

            var def = this.def;
            for (var i = 0; i < type.length; i++) {

                // Reject idporten with higher priority than accept with 'all'.
                if (def[i] === 'idporten' && type[i] !== 'idporten') {
                    return false;

                } else if (type[i] === 'all') {
                    return true;

                } else if (i > (def.length-1)) {

                    return false;

                } else if (type[i] !== def[i]) {
                    return false;
                }
            }
            return true;
        },

        "getView": function() {
            var view = {
                id: this.id,
                type: this.type,
                subid: this.subid,
                classes: "",
                title: this.title,
                distance: this.distance,
                activeAccounts: this.activeAccounts,
                directAccount: this.directAccount,
                enforceLogout: this.enforceLogout,
                logout: true,
                showActive: this.activeAccounts || this.directAccount
            };
            if (this.hasOwnProperty("logout")) {
                view.logout = this.logout;
            }
            if (this.iconImage) {
                view.logo = '/static/media/disco/' + this.iconImage;
            } else if (this.icon) {
                view.icon = this.icon;
            }

            if (this.activeAccounts) {
                view.classes = 'hasactive';
            }
            return view;
        }

    });

    return Provider;

});
