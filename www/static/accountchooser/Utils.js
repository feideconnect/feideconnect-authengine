define(function(require, exports, module) {
	"use strict";

    var Utils = {};

    Utils.quoteattr = function(s, preserveCR) {
        preserveCR = preserveCR ? '&#13;' : '\n';
        return ('' + s) /* Forces the conversion to string. */
            .replace(/&/g, '&amp;') /* This MUST be the 1st replacement. */
            .replace(/'/g, '&apos;') /* The 4 other predefined entities, required. */
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            /*
            You may add other replacements here for HTML only 
            (but it's not necessary).
            Or for XML, only if the named entities are defined in its DTD.
            */ 
            .replace(/\r\n/g, preserveCR) /* Must be before the next replacement. */
            .replace(/[\r\n]/g, preserveCR);
    };


    // calculate distance between two locations
    Utils.calculateDistance = function (lat1, lon1, lat2, lon2) {
        var R = 6371; // km
        var dLat = Utils.toRad(lat2-lat1);
        var dLon = Utils.toRad(lon2-lon1); 
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(Utils.toRad(lat1)) * Math.cos(Utils.toRad(lat2)) * 
                Math.sin(dLon/2) * Math.sin(dLon/2); 
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        var d = R * c;
        return d;
    };

    Utils.toRad = function (deg) {
        return deg * Math.PI/180;
    };


    // Normalize search term.
    Utils.normalizeST = function(searchTerm) {
        var x = searchTerm.toLowerCase().replace(/\W/g, '');
        if (x === '') {
            return null;
        }
        return x;
    }

    // Is search length ok?
    Utils.stok = function(str) {
        // console.log("STR", str);
        if (str === null) {return true;}
        if (str.length > 2) { return true; }
        return false;
    }



    return Utils;


});