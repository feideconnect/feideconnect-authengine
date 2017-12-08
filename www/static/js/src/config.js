var cachebuster = document.body.getAttribute('data-cachebuster');
var requirecfg = {
    baseUrl: '/static',
    paths: {
        src: 'js/src',
        jquery: 'components/jquery/dist/jquery',
        selectize: 'components/selectize/dist/js/standalone/selectize',
        dustjs: 'components/dustjs-linkedin/dist/dust-full.min',
        jscookie: 'components/js-cookie/src/js.cookie',
        es6promise: 'components/es6-promise/es6-promise.min',
        vex: 'components/vex/dist/js/vex.combined',
        accountchooser: 'accountchooser',
        oauthgrant: 'oauthgrant',
    },
    shim: {
        selectize: ['jquery']
    },
    urlArgs: 'bust='+cachebuster
};
