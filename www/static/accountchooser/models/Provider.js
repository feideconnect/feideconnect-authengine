define(function(require, exports, module) {
    "use strict";

    var
        Model = require('./Model'),
        Utils = require('../Utils')
        ;

    var Provider = Model.extend({

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

            this.distanceFrom = cacheStr;
            return this.distance;
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

        "getHTML": function() {
            console.error("Provider.getHTML() DEPRECATED");
            var txt = '';
            var datastr = 'data-id="' + Utils.quoteattr(this.entityID) + '" data-type="saml"';
            txt += '<a href="#" class="list-group-item idpentry" ' + datastr + '>' +
                '<div class="media"><div class="media-left media-middle" style="">';

            if (this.logo) {
                txt += '<div class="" style="width: 64px; align: right"><img class="media-object" style="float: right; max-height: 64px" src="/metadata/logo/?entityid=' + Utils.quoteattr(this.entityID) + '" alt="Provider logo"></div>';
            } else {
                txt += '<div class="media-object" style="width: 64px; text-align: right">&nbsp;</div>';
            }

            var dt = '';
            if (this.descr) {
                dt = '<p style="margin: 0px 0px 0px 10px; color: #888; font-size: 90%">' + Utils.quoteattr(this.descr) + '</p>';
            }
            txt +=  '</div>' +
                    '<div class="media-body">' +
                        '<p style="margin: 0px 0px 0px 10px">' + Utils.quoteattr(this.title) + '</p>' +
                        dt  +
                     '</div>' +
                '</div>' +
            '</a>';
            return txt;
        }

    });

    return Provider;

});
