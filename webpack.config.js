var encoreConfig = require('./webpack-encore-config');

module.exports = encoreConfig('tests/Functional/App/public/build/').getWebpackConfig();
