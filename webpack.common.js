const path = require('path')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const UnusedWebpackPlugin = require('unused-webpack-plugin')
const Visualizer = require('webpack-visualizer-plugin2')

module.exports = {
  entry: {
    script: path.join(__dirname, 'js-src', 'main', 'index.js'),
    settings: path.join(__dirname, 'js-src', 'settings', 'index.js')
  },
  output: {
    filename: 'js/[name].js',
    path: __dirname,
    chunkFilename: 'js/[name].js?v=[contenthash]'
  },
  resolve: {
    symlinks: false,
    modules: [__dirname, 'node_modules']
  },
  module: {
    rules: [
      {
        test: /\.(jpe?g|png|gif|svg)$/,
        loader: 'url-loader',
        options: {
          limit: 10240
        }
      }, {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            plugins: [
              '@babel/plugin-syntax-dynamic-import',
              '@babel/plugin-proposal-class-properties'
            ],
            presets: [
              '@babel/preset-env',
              '@babel/preset-react'
            ]
          }
        }
      }, {
        test: /\.css$/,
        use: ['style-loader', 'css-loader']
      }, {
        test: /\.html$/i,
        loader: 'html-loader'
      },
      {
        test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              outputPath: 'fonts/'
            }
          }
        ]
      }]
  },
  plugins: [
    new Visualizer({
      filename: './statistics.html'
    }),
    new UnusedWebpackPlugin({
      directories: [path.join(__dirname, 'js-src')]
    }),
    new MiniCssExtractPlugin({
      filename: '[name].css'
    })
  ]
}
