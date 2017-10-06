define(function(require, exports, module) {
    "use strict";

    var
        Model = require('./Model'),
        Utils = require('../Utils')
        ;



    var NorwegianOrg = Model.extend({
        "init": function(a) {
            a.country = 'no';
            this._super(a);
        },
        "getDistance": function(loc) {
            var minDistance = 9999;
            if (this.hasOwnProperty("uiinfo") && this.uiinfo.hasOwnProperty("geo")) {
                for (var i = 0; i < this.uiinfo.geo.length; i++) {
                    var geo = this.uiinfo.geo[i];
                    var dist = Utils.calculateDistance(loc.lat, loc.lon, geo.lat, geo.lon);
                    if (dist < minDistance) {
                        minDistance = dist;
                    }
                }
            }
            return minDistance;
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
        "getHTML": function(feideIdP) {

            var classes = '';
            if (!this.isEnabled()) {
                classes += ' disabled';
            }
            var txt = '';
            var datastr = 'data-id="' + Utils.quoteattr(feideIdP) + '" data-subid="' + Utils.quoteattr(this.id) + '" data-type="saml"';
            txt += '<a href="#" class="list-group-item idpentry' + classes + '" ' + datastr + '>' +
                '<div class="media"><div class="media-left media-middle">' +
                        '<img class="media-object" style="width: 48px; height: 48px" src="https://api.dataporten.no/orgs/fc:org:' + this.id + '/logo" alt="...">' +
                    '</div>' +
                    '<div class="media-body"><p>' + Utils.quoteattr(this.title) + '</p></div>' +
                '</div>' +
            '</a>';
            return txt;
        }

    });

    return NorwegianOrg;

});
