var encoreConfig = require('./webpack-encore-config');

module.exports = encoreConfig('tests/App/public/build/').getWebpackConfig();
