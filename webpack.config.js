// required
const path = require( 'path' );
const webpack = require( 'webpack' );
const devMode = false; //process.env.NODE_ENV !== 'production';
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const OptimizeCssAssetsPlugin = require( 'optimize-css-assets-webpack-plugin' );
const FixStyleOnlyEntriesPlugin = require( 'webpack-fix-style-only-entries' );
const UglifyJsPlugin = require( 'uglifyjs-webpack-plugin' )

// extract sass
const extractSCSS = new MiniCssExtractPlugin( {
	path: path.resolve( __dirname ),
	filename: '[name].css'
} );

// fix separate styles
const fixSCSS = new FixStyleOnlyEntriesPlugin();

// scss config
const scssConfig = [
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
	devMode ? 'style-loader' : MiniCssExtractPlugin.loader,
	'css-loader',
	{
		loader: 'sass-loader',
		options: {
			outputStyle: devMode ? 'expanded' : 'compressed'
		}
	}
];

// webpack plugins
const plugins = [
	extractSCSS,
	fixSCSS
];

// webpack config
module.exports = {
	context: __dirname,
	cache: false,
	devtool: devMode ? 'inline-sourcemap' : false,
	mode: devMode ? 'development' : 'production',
	entry: {
		'js/gutenberg.min': './src/gutenberg.js',
		"css/gutenberg.min": './src/gutenberg.scss'
	},
	output: {
		path: path.resolve( __dirname ),
		filename: '[name].js',
		/* filename: ( chunkData ) => {
			return chunkData.chunk.name === 'css/gutenberg.min' ? '[name].css': '[name].js';
		}, */
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
				use: scssConfig
			},
		]
	},
	optimization: {
		// minimize in dev mode only
		minimizer: ! devMode
			? [
				// enable the js minification plugin
				new UglifyJsPlugin( {
					cache: false,
					parallel: true,
					extractComments: false
				} ),
				// enable the css minification plugin
				new OptimizeCssAssetsPlugin( { } )
			]
			: [ ]
	},
	plugins: plugins
};