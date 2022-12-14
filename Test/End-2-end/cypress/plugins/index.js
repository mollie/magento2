const webpackPreprocessor = require('@cypress/webpack-preprocessor')
const TestRailReporter = require('cypress-testrail');

module.exports = (on, config) => {
  const customCommand = 'Magento';
  new TestRailReporter(on, config, customCommand).register();

  on('file:preprocessor', webpackPreprocessor({
    webpackOptions: require('../../webpack.config'),
  }))

  on('before:browser:launch', (browser = {}, launchOptions) => {
    if (browser.name === 'chrome' || browser.name === 'edge') {
      launchOptions.args.push('--disable-features=SameSiteByDefaultCookies')
      return launchOptions
    }
  })
}
