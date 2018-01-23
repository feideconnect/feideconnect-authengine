#!/bin/bash

set -x

mkdir -p www/static/build

# Dust templates
#./node_modules/dustjs-linkedin/bin/dustc --amd templates/*.dust > www/static/accountchooser/templates.js
ls -la www/static/components
ls -la www/static/components
ls -la www/static/components/dustjs-linkedin/bin/

www/static/components/dustjs-linkedin/bin/dustc templates/dust_providerlist.dust www/static/build/dust_providerlist.dust.js
www/static/components/dustjs-linkedin/bin/dustc templates/dust_accountlist.dust www/static/build/dust_accountlist.dust.js



echo -n 'define(["dust"], function() { ' > www/static/build/dust_templates.js
cat www/static/build/*.dust.js >> www/static/build/dust_templates.js
echo '})' >> www/static/build/dust_templates.js


# Javascript
node_modules/requirejs/bin/r.js -o build.js baseUrl=./www/static/js/src out=./www/static/build/script.min.js

# CSS
mkdir -p ./www/static/css/build
sass www/static/css/src/base.scss www/static/css/src/base.css
node_modules/requirejs/bin/r.js -o cssIn=./www/static/css/src/base.css out=./www/static/css/build/styles.min.css
