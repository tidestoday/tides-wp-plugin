module.exports = {
	entry: './src/js/editor.js',
	output: {
		path: __dirname,
		filename: 'src/editor.js',
	},
	module: {
		loaders: [
			{
				test: /.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
		],
	},
};