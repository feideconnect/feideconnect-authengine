/**
 * This file runs custom javascript code that should be run for all pages.
 *
 * Here are the features you get:
 *
 * - Detect javascript support. If you want to know if javascript is enabled, add an input to your form with the
 * 'js_support' ID.
 *
 * - Detect page inside an iframe. If you want to know if we are running inside an iframe, add an input to your form
 * with the 'inside_iframe' ID. You can also check that from your script by checking the "inside_iframe" variable.
 */

var inside_iframe = window.top !== window.self;

$(document).ready(function() {
    $(".selectize").selectize();

    // check for javascript support
    if (document.getElementById('js_support')) {
        $('#js_support').val('true');
    }

    // check if we are inside an iframe
    if (document.getElementById('inside_iframe') && inside_iframe) {
        $('#inside_iframe').val('true');
    }
});
