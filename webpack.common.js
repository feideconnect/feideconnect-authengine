const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

module.exports = {
    entry: {
        main: './src/app/main',
        accountchooser: './src/accountchooser/main',
        oauth: './src/oauthgrant/main',
    },
    output: {
        path: path.resolve(__dirname, 'www/static'),
        filename: '[name].bundle.js'
    },
    plugins: [
        // This plugin separates common libs into a separate bundle
        new webpack.optimize.CommonsChunkPlugin({
            name: 'common'
        }),
        // Bundles css
        new ExtractTextPlugin({
            filename: 'bundle.css'
        }),
        // Provider for Dust-templates
        new webpack.ProvidePlugin({
            dust: 'dustjs-linkedin'
        })
    ],
    module: {
        rules: [
            {
                // Finds and bundles scss and css files
                test: /\.s?css$/,
                use: ExtractTextPlugin.extract({
                    use: ['css-loader', 'sass-loader']
                })
            },
            {
                // Finds and bundles images
                test: /\.(png|jp(e*)g|svg)$/,
                use: [{
                        loader: 'file-loader',
                        options: {}
                    }]
            },
            {
                // Finds and compiles dust templates
                test: /\.dust$/,
                loader: 'dust-loader'
            },
            {
                // Makes sure we everything works for ES5 browsers
                // Should work down to IE 9
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['babel-preset-env']
                    }
                }
            },
            {
                test: /.(ttf|otf|eot|svg|woff(2)?)(\?[a-z0-9]+)?$/,
                use: [{
                    loader: 'file-loader',
                    options: {
                        name: '[name].[ext]',
                    }
                }]
            },
        ]
    }
}
