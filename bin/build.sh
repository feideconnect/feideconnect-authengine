#!  /bin/bash

echo "About to build Javascript and CSS"
node_modules/requirejs/bin/r.js -o build.js baseUrl=./www/static/accountchooser out=./www/static/build/accountchooser.min.js
node_modules/requirejs/bin/r.js -o build.js baseUrl=./www/static/oauthgrant out=./www/static/build/oauthgrant.min.js
node_modules/requirejs/bin/r.js -o cssIn=./www/static/css/style.css out=./www/static/build/style.min.css
