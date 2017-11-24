define.amd.dust = true;
requirejs.config(requirecfg);
define(function (require) {

    var $ = require('jquery');
    require('selectize');

    // get available languages
    var languages = $.map($('#mobile_language_selector option'), function (option) {
        return option.text.toLowerCase();
    });
    $('#languageSelector').on("change", function (e) {
        if (-1 !== $.inArray(
                $('#mobile_language_selector-selectized').prev().text().toLowerCase(),
                languages
            )
        ) {
            e.currentTarget.submit();
        }
    });
    $(".selectize").selectize();

    // check for javascript support
    if (document.getElementById('js_support')) {
        $('#js_support').val('true');
    }

    // check if we are inside an iframe
    var inside_iframe = window.top !== window.self;
    if (document.getElementById('inside_iframe') && inside_iframe) {
        $('#inside_iframe').val('true');
    }

    require('src/dialog');

    if (typeof Promise !== "function") {
        require ('es6promise').polyfill();
    }

    // Configure console if not defined. A fix for IE <= 9.
    if (!window.console) {
        window.console = {
            "log": function() {},
            "error": function() {}
        };
    }

    // require and load the apps we have, passing the current page so that they can determine
    // if they need to run or not
    var curpage = $('body').attr('id');
    var AccountChooser = require('accountchooser/App');
    var ac = new AccountChooser(curpage);
    var OauthGrant = require('oauthgrant/App');
    var og = new OauthGrant(curpage);
});
