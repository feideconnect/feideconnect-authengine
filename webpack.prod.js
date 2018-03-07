const webpack = require('webpack');
const merge = require('webpack-merge');
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');
const AssetsPlugin = require('assets-webpack-plugin')
const common = require('./webpack.common');

module.exports = merge(common, {
    output: {
        filename: '[name].[chunkhash].js'
    },
    plugins: [
        // Minify
        new UglifyJSPlugin(),
        // Make NODE_ENV available for the scripts
        new webpack.DefinePlugin({
            'process.env.NODE_ENV': JSON.stringify('production')
        }),
        // Creates webpack-assets.json which maps entries to correct filenames
        new AssetsPlugin({filename: 'etc/assets.json'})
    ]
})
