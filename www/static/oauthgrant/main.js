define(function(require, exports, module) {
    "use strict";

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
