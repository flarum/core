const config = require('flarum-webpack-config');
const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');
const merge = require('webpack-merge');
const TerserPlugin = require("terser-webpack-plugin");

const useBundleAnalyzer = process.env.ANALYZER === 'true';

const plugins = [];

if (useBundleAnalyzer) {
  plugins.push(new BundleAnalyzerPlugin());
}

module.exports = merge(config(), {
  output: {
    library: 'flarum.core',
  },

  // temporary TS configuration
  resolve: {
    extensions: ['.ts', '.tsx', '.js', '.json'],
  },

  plugins,
  optimization: {
    minimizer: [new TerserPlugin({
      extractComments: false,
    })],
  },
});

module.exports['module'].rules[0].test = /\.(tsx?|js)$/;
module.exports['module'].rules[0].use.options.presets.push('@babel/preset-typescript');
