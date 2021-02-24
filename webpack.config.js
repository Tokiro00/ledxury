const path                 = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin    = require('copy-webpack-plugin');
const ImageminPlugin       = require('imagemin-webpack-plugin').default;
const BrowserSyncPlugin    = require('browser-sync-webpack-plugin');
const PurgeCSS             = require('@fullhuman/postcss-purgecss');
const UglifyJsPlugin       = require("uglifyjs-webpack-plugin");
//const JQueryPlugin       = require("jquery");
const isProduction         = 'production' === process.env.NODE_ENV;

// Set the build prefix.
let prefix = isProduction ? '.min' : '';

// Set the PostCSS Plugins.
const post_css_plugins = [
	require('postcss-import'),
	require('tailwindcss')('./tailwind.config.js'),
	require('postcss-nested'),
	require('postcss-custom-properties'),
	require('autoprefixer')
]

// Add PurgeCSS for production builds.
if ( isProduction ) {
	post_css_plugins.push(require('cssnano'));
	post_css_plugins.push(
		PurgeCSS({
			content: [
				'./public/assets/images/**/*.svg',
        		'./public/assets/css/**/*.css',
        		'./public/assets/css/**/*.scss',
			],
			css: [
				'./node_modules/tailwindcss/dist/base.css'
			],
			extractors: [
				{
					extractor: content =>
            content.match(/[A-Za-z0-9-_:\/]+/g) || [],
					extensions: ['php', 'js', 'svg', 'css',]
				}
			],
			whitelistPatterns: getCSSWhitelistPatterns()
		})
	)
}

const config = {
	entry: './public/assets/js/main.js',
	optimization: {
		minimizer: [
			new UglifyJsPlugin({
				cache: true,
				parallel: true,
				sourceMap: true
			})
		]
	},
	output: {
		filename: `[name]${prefix}.js`,
		path: path.resolve(__dirname, 'public/dist')
	},
	mode: process.env.NODE_ENV,
	module: {
		rules: [
			{
				test: /\.js$/,
				loader: 'babel-loader',
				options: {
					presets: [
						[
							"@babel/preset-env"
						]
					]
				}
			},
			{
				test: /\.css$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							importLoaders: 1,
							sourceMap: ! isProduction
						}
					},
					{
						loader: 'postcss-loader',
						options: {
							ident: 'postcss',
							sourceMap: isProduction || 'inline',
							plugins: post_css_plugins,
						},
					}
				],
			}
		]
	},
	resolve: {
		alias: {
			'@'      : path.resolve('assets'),
			'@images': path.resolve('../images')
		}
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: `[name]${prefix}.css`,
		}),
		new CopyWebpackPlugin({
			patterns: [
				{
					from: './public/assets/images/',
					to: 'images'
				}
			]
		}),
		new ImageminPlugin({ test: /\.(jpe?g|png|gif|svg)$/i })
	]
}

// Fire up a local server if requested
//if (process.env.SERVER) {
//	config.plugins.push(
//		new BrowserSyncPlugin(
//			{
//				proxy: 'oakland-promise.test',
//				files: [
//					'**/*.php',
//					'**/*.scss',
//          '**/*.js'
//				],
//				port: 3000,
//				notify: false,
//        open: false
//			}
//		)
//	)
//}

/**
 * List of RegExp patterns for PurgeCSS
 * @returns {RegExp[]}
 */
function getCSSWhitelistPatterns() {
	return [
		/^home(-.*)?$/,
		/^blog(-.*)?$/,
		/^archive(-.*)?$/,
		/^date(-.*)?$/,
		/^error404(-.*)?$/,
		/^admin-bar(-.*)?$/,
		/^search(-.*)?$/,
		/^nav(-.*)?$/,
		/^wp(-.*)?$/,
		/^screen(-.*)?$/,
		/^navigation(-.*)?$/,
		/^(.*)-template(-.*)?$/,
		/^(.*)?-?single(-.*)?$/,
		/^postid-(.*)?$/,
		/^post-(.*)?$/,
		/^attachmentid-(.*)?$/,
		/^attachment(-.*)?$/,
		/^page(-.*)?$/,
		/^(post-type-)?archive(-.*)?$/,
		/^author(-.*)?$/,
		/^category(-.*)?$/,
		/^tag(-.*)?$/,
		/^menu(-.*)?$/,
		/^tags(-.*)?$/,
		/^tax-(.*)?$/,
		/^term-(.*)?$/,
		/^date-(.*)?$/,
		/^(.*)?-?paged(-.*)?$/,
		/^depth(-.*)?$/,
		/^children(-.*)?$/,
	];
}

module.exports = config
