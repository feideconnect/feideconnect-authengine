const path = require('path');
const webpack = require('webpack');

module.exports = {
    entry: {
        main: './src/app/main',
        accountchooser: './src/accountchooser/main',
        oauth: './src/oauthgrant/main',
    },
    output: {
        path: path.resolve(__dirname, 'www/static/js'),
        filename: '[name].bundle.js'
    },
    plugins: [
        // This plugin separates common libs into a separate bundle
        new webpack.optimize.CommonsChunkPlugin({
            name: 'common'
        })
    ]
}
