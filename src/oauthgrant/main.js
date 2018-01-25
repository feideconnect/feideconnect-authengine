const jquery = require('jquery');

if (typeof Promise !== "function") {
    require('es6-promise').polyfill();
}

// Configure console if not defined. A fix for IE <= 9.
if (!window.console) {
    window.console = {
        "log": function() {},
        "error": function() {}
    };
}

const App = require('./App');
$(document).ready(function() {
    const app = new App();
});
