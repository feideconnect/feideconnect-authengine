

var requirecfg = {
    // baseUrl: '/static',
    paths: {
        // src: 'js/src',
        jquery: '../../components/jquery/dist/jquery',
        selectize: '../../components/selectize/dist/js/standalone/selectize',
        dust: '../../components/dustjs-linkedin/dist/dust-core.min',
        jscookie: '../../components/js-cookie/src/js.cookie',
        es6promise: '../../components/es6-promise/es6-promise.min',
        vex: '../../components/vex/dist/js/vex.combined',
        accountchooser: '../../accountchooser',
        oauthgrant: '../../oauthgrant',
        templates: '../../build'
    },
    shim: {
        selectize: ['jquery']
    }
}

define.amd.dust = true;
requirejs.config(requirecfg);

define(function (require) {

    require('dust');

    require('templates/dust_accountlist.dust')
    require('templates/dust_providerlist.dust')


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

    require('dialog');

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
