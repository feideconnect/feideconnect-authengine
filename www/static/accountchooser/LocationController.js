define(function(require, exports, module) {
    "use strict";

    var Class = require('./Class');


    var getLocation = function(callback) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(callback);
        } else {
            console.error("Geolocation is not supported by this browser.");
        }
    };


    var LocationController = Class.extend({
        "init": function(app, loc) {

            var that = this;
            this.app = app;
            this.loc = {};
            if (loc) {
                this.loc.lat = loc.lat;
                this.loc.lon = loc.lon;
                this.loc.title = loc.title;
                this.loc.stored = false;
            }
            this.fetchLocation();
            this._callback = null;

            $("#updatelocation").on("click", function(e) {
                e.preventDefault();

                getLocation(function(location) {
                    that.loc.lat = location.coords.latitude;
                    that.loc.lon = location.coords.longitude;
                    that.loc.title = that.loc.lat.toFixed(3) + ', ' + that.loc.lon.toFixed(3);

                    that.saveLocation(that.loc);
                    that.executeCallback();
                });

            });

            $("#removelocation").on("click", function(e) {
                that.deleteLocation();
                that.fetchLocation();
                that.executeCallback();
            });

        },

        "executeCallback": function() {
            var loc = this.getLocation();
            if (this._callback !== null) {
                this._callback(loc);
            }
        },

        "fetchLocation": function() {
            var stored = this.getStoredLocation();
            if (stored !== null) {
                this.loc.lat = stored.lat;
                this.loc.lon = stored.lon;
                this.loc.title = stored.title;
                this.loc.stored = true;
            }
            return this.loc;
        },

        "getStoredLocation": function() {
            if (!window.localStorage) {
                return null;
            }
            var locraw = localStorage.getItem("location");
            var loc = JSON.parse(locraw);
            return loc;
        },

        "saveLocation": function(loc) {
            if (!window.localStorage) {
                return;
            }

            this.loc.stored = true;
            localStorage.setItem("location", JSON.stringify(loc));
        },

        "deleteLocation": function() {
            localStorage.removeItem("location");
            this.loc.stored = false;
        },

        "getLocation": function() {
            return this.loc;
        },
        "onUpdate": function(callback) {
            this._callback = callback;
        }
    });


    return LocationController;
});
