#!/bin/bash

set -x

# Dust templates
#./node_modules/dustjs-linkedin/bin/dustc --amd templates/*.dust > www/static/accountchooser/templates.js
www/static/components/dustjs-linkedin/bin/dustc templates/dust_providerlist.dust www/static/build/dust_providerlist.dust.js
www/static/components/dustjs-linkedin/bin/dustc templates/dust_accountlist.dust www/static/build/dust_accountlist.dust.js



# Javascript
mkdir -p ./www/static/build
node_modules/requirejs/bin/r.js -o build.js baseUrl=./www/static/js/src out=./www/static/build/script.min.js

# CSS
mkdir -p ./www/static/css/build
sass www/static/css/src/base.scss www/static/css/src/base.css
node_modules/requirejs/bin/r.js -o cssIn=./www/static/css/src/base.css out=./www/static/css/build/styles.min.css
