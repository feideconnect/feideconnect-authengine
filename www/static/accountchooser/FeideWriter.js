define(function(require, exports, module) {
    "use strict";

    var Class = require('./Class');

    var FeideWriter = Class.extend({

        "init": function(app, org, feideid) {

            this.org = org;
            this.app = app;
            this.feideid = feideid;
            this.timer = null;

            var feideIdPEndpoints = {
                'https://idp-test.feide.no': 'https://idp-test.feide.no/simplesaml/module.php/feide/preselectOrg.php',
                'https://idp.feide.no': 'https://idp.feide.no/simplesaml/module.php/feide/preselectOrg.php'
            };

            if (!feideIdPEndpoints.hasOwnProperty(this.feideid)) {
                console.error("Trying to look up ", this.feideid, "from ", feideIdPEndpoints);
                throw new Error("Bad Feide entityID. No configuration found.");
            }

            var returnURL = window.location.origin + '/accountchooser/response';
            this.url = feideIdPEndpoints[this.feideid] + '?HomeOrg=' + encodeURIComponent(org) + '&ReturnTo=' + encodeURIComponent(returnURL);

            var that = this;

            this._callback = null;
            $("#iloaded").on("click", function() {
                if (that.timer) {
                    clearTimeout(that.timer);
                }
                // console.error(" ---- Detected click on iloaded...");
                if (that._callback) {
                    that._callback();
                    that._callback = null;
                }

            });

        },

        "load": function() {

            var that = this;
            var iframe = '<iframe style="display: none" src="' + this.url + '"></iframe>';
            $("body").prepend(iframe);

            this.timer = setTimeout(function() {

                that.app.setErrorMessage("Unable to preselect organization at Feide", "warning");

                setTimeout(function() {

                    if (that._callback) {
                        that._callback();
                        that._callback = null;
                    }
                }, 1500);

            }, 3000);

            return this;

        },
        "onLoad": function(callback) {
            // console.error(" ---- Setting onload callback");
            this._callback = callback;
            return this;
        }

    });

    return FeideWriter;
});
