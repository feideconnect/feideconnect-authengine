#!/bin/bash

set -x

mkdir -p ./www/static/build

# Minify javascript and css files
# node_modules/requirejs/bin/r.js -o build.js baseUrl=./www/static/accountchooser out=./www/static/build/accountchooser.min.js
# node_modules/requirejs/bin/r.js -o build.js baseUrl=./www/static/oauthgrant out=./www/static/build/oauthgrant.min.js
# node_modules/requirejs/bin/r.js -o cssIn=./www/static/css/src/style.css out=./www/static/css/src/style.min.css

# Bundle css-files
find ./www/static/css/lib/ -name '*.css' -exec cat {} + > ./www/static/build/bundle-lib.css
find ./www/static/css/src/ -name '*.css' -exec cat {} + > ./www/static/build/bundle-src.css

# Compile dust templates
./node_modules/dustjs-linkedin/bin/dustc --amd templates/*.dust > www/static/accountchooser/templates.js
