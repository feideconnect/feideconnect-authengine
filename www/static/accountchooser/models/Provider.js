define(function(require, exports, module) {
    "use strict";   

    var 
        Model = require('./Model'),
        Utils = require('../Utils')
        ;

    var Provider = Model.extend({

        "getDistance": function(loc) {

            if (this.hasOwnProperty("geo") && this.geo.hasOwnProperty("lat") && this.geo.hasOwnProperty("lon")) {
                // console.error("Compare ", loc.lat, loc.lon, this.geo.lat, this.geo.lon);
                var dist = Utils.calculateDistance(loc.lat, loc.lon, this.geo.lat, this.geo.lon);
                // console.error("Dist is ", dist);
                return dist;
            }

            return 9999;
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
            
            var txt = '';
            var datastr = 'data-id="' + Utils.quoteattr(this.entityID) + '" data-subid="' + Utils.quoteattr(this.entityID) + '" data-type="saml"';
            txt += '<a href="#" class="list-group-item idpentry" ' + datastr + '>' +
                '<div class="media"><div class="media-left media-middle" style="">';

            if (this.icon) {
                txt += '<div class="" style="width: 200px; align: right"><img class="media-object" style="float: right; max-height: 48px" src="https://api.discojuice.org/logo/' + this.icon + '" alt="..."></div>';
            } else {
                txt += '<div class="media-object" style="width: 200px; text-align: right">&nbsp;</div>';
            }
            

            txt +=  '</div>' +
                    '<div class="media-body"><p style="margin-left: 10px">' + Utils.quoteattr(this.title) + '</p></div>' +
                '</div>' +
            '</a>';
            return txt;
        }
        
    });

    return Provider;

});
