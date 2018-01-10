#!/bin/bash

set -x

mkdir -p ./www/static/build



# Compile dust templates
./node_modules/dustjs-linkedin/bin/dustc --amd templates/*.dust > www/static/accountchooser/templates.js
# CSS
mkdir -p ./www/static/css/build
sass www/static/css/src/base.scss www/static/css/src/base.css
node_modules/requirejs/bin/r.js -o cssIn=./www/static/css/src/base.css out=./www/static/css/build/styles.min.css
