({
    "name": "../components/almond/almond",
    "wrap": true,
    "insertRequire": ["main"],
    "include": "main",
    "paths": {
      "jquery": "../components/jquery/dist/jquery.min",
      "bootstrap": "../components/bootstrap/dist/js/bootstrap.min",
      "tooltip": "../components/bootstrap/js/tooltip"
    },
    "shim": {
        "bootstrap": ["jquery"],
        "tooltip": ["jquery"]
    }
})
