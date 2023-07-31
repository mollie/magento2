const webpackPreprocessor = require('@cypress/webpack-preprocessor')

module.exports = (on, config) => {
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
