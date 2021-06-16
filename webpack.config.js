const defaultConfig = require( './node_modules/@wordpress/scripts/config/webpack.config.js' );

module.exports = {
	...defaultConfig,
	entry: {
		frontend: './src/frontend.js',

		block_slider_backend: './blocks/src/slider/backend.js',
		block_slider_frontend: './blocks/src/slider/frontend.js',
		block_slider_splide: './blocks/src/slider/libs/splide_import.js',
	},
};
