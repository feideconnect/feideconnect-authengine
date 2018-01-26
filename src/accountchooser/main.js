const $ = require('jquery');
const App = require('./App');
$(document).ready(function() {
    if ($('body').attr('id') === 'accountchooser') {
        var app = new App('accountchooser');
    }
});
