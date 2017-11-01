({
    "name": "../components/requirejs/require",
    "wrap": true,
    "insertRequire": ["main"],
    "include": "main",
    "paths": {
      "jquery": "../components/jquery/dist/jquery.min",
      "bootstrap": "../components/bootstrap/dist/js/bootstrap.min",
      "tooltip": "../components/bootstrap/js/tooltip",
      "dust": "../components/dustjs-linkedin/dist/dust-full.min"
    },
    "shim": {
        "bootstrap": ["jquery"],
        "tooltip": ["jquery"]
    }
})
