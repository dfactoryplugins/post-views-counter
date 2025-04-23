// required
const path = require('path');
const webpack = require('webpack');
const devMode = false; //process.env.NODE_ENV !== 'production';
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FixStyleOnlyEntriesPlugin = require('webpack-fix-style-only-entries');

// extract sass
const extractSCSS = new MiniCssExtractPlugin({
	filename: '[name].css'
});

// fix separate styles
const fixSCSS = new FixStyleOnlyEntriesPlugin();

// webpack config
module.exports = {
	context: __dirname,
	cache: false,
	devtool: devMode ? 'inline-sourcemap' : false,
	mode: devMode ? 'development' : 'production',
	entry: {
		'js/block-editor.min': './src/block-editor.js',
		"css/block-editor.min": './src/block-editor.scss'
	},
	output: {
		path: path.resolve(__dirname),
		filename: '[name].js',
	},
	watch: false,
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: [
					{
						loader: 'babel-loader'
					}
				]
			},
			{
				test: /\.(sa|sc|c)ss$/,
				exclude: /node_modules/,
				use: [
					/* extract into separate scss files
					{
						loader: 'file-loader',
						options: {
							name: '[name].min.css',
							context: './',
							outputPath: '/',
							publicPath: path.resolve( __dirname, 'css' ),
						}
					},
					{
						loader: 'extract-loader'
					},
					*/
					// Inject CSS into the DOM.
					devMode ? 'style-loader' : MiniCssExtractPlugin.loader,
					{
						// This loader resolves url() and @imports inside CSS
						loader: 'css-loader',
					},
					{
						// Then we apply postCSS fixes like autoprefixer and minifying
						loader: 'postcss-loader',
					},
					{
						// First we transform SASS to standard CSS
						loader: 'sass-loader',
						options: {
							implementation: require('sass'),
							sassOptions: {
								style: devMode ? 'expanded' : 'compressed'
							}
						}
					}
				]
			},
		]
	},
	optimization: {
	},
	plugins: [
		extractSCSS,
		fixSCSS
	]
};