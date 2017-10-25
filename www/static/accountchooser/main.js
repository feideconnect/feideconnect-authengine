define(function(require, exports, module) {
    "use strict";

    var jquery = require('jquery');
    var bootstrap = require('bootstrap');
    var tooltip = require('tooltip');

    if (typeof Promise !== "function") {
        require('../components/es6-promise/es6-promise.min').polyfill();
    }

    // Configure console if not defined. A fix for IE <= 9.
    if (!window.console) {
        window.console = {
            "log": function() {},
            "error": function() {}
        };
    }

    var App = require('App');
    $(document).ready(function() {
        var app = new App();
    });

});
