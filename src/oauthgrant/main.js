define(function(require) {
    "use strict";

    define.amd.dust = true;

    var jquery = require('jquery');

    if (typeof Promise !== "function") {
        require('es6promise').polyfill();
    }

    // Configure console if not defined. A fix for IE <= 9.
    if (!window.console) {
        window.console = {
            "log": function() {},
            "error": function() {}
        };
    }

    var App = require('./App');
    $(document).ready(function() {
        var app = new App();
    });

});
