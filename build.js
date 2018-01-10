({
    "_name": "../../components/requirejs/require",
    "name": "../../components/almond/almond",
    "wrap": true,
    "insertRequire": ["main"],
    "include": "main",
    "paths": {
      "jquery": "../../components/jquery/dist/jquery.min",
      "bootstrap": "../../components/bootstrap/dist/js/bootstrap.min",
      "tooltip": "../../components/bootstrap/js/tooltip",
      "selectize": "../../components/selectize/dist/js/standalone/selectize",
      "es6promise": "../../components/es6-promise/es6-promise.min",
      "vex": "../../components/vex/dist/js/vex.combined",
      "accountchooser": "../../accountchooser",
      "oauthgrant": "../../oauthgrant",
      "jscookie": "../../components/js-cookie/src/js.cookie",
      "dust": "../../components/dustjs-linkedin/dist/dust-core.min",
        templates: '../../build'
    },
    "shim": {
        selectize: ['jquery']
    }
})
