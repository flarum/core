const config = require('flarum-webpack-config');
const merge = require('webpack-merge');

module.exports = merge(config(), {
  output: {
    library: 'flarum.core'
  },

  // temporary TS configuration
  resolve: {
    extensions: ['.ts', '.tsx', '.js', '.json'],
  },
});

module.exports['module'].rules[0].test = /\.(tsx?|js)$/;
module.exports['module'].rules[0].use.options.presets.push('@babel/preset-typescript');
