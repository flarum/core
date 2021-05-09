const config = require('flarum-webpack-config');
const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');
const merge = require('webpack-merge');

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
});

module.exports['module'].rules[0].test = /\.(tsx?|js)$/;
module.exports['module'].rules[0].use[1].options.presets.push('@babel/preset-typescript');
