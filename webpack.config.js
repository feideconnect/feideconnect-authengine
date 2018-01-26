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
        new ExtractTextPlugin({
            filename: 'bundle.css'
        }),
        new webpack.ProvidePlugin({
            dust: 'dustjs-linkedin'
        })
    ],
    module: {
        rules: [
            {
                test: /\.s?css$/,
                use: ExtractTextPlugin.extract({
                    use: ['css-loader', 'sass-loader']
                })
            },
            {
                test: /\.(png|jp(e*)g|svg)$/,
                use: [{
                        loader: 'file-loader',
                        options: {}
                    }]
            },
            {
                test: /\.dust$/,
                loader: 'dust-loader'
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['babel-preset-env']
                    }
                }
            }
        ]
    }

}
