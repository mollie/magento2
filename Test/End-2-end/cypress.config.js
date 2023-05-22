const { defineConfig } = require("cypress");

const defaultProductId = process.env.DEFAULT_PRODUCT_ID || 2;

module.exports = defineConfig({
  projectId: "44bnds",
  chromeWebSecurity: false,
  env: {
    defaultProductId: defaultProductId,
  },
  e2e: {
    experimentalWebKitSupport: true,
    setupNodeEvents(on, config) {
      return require('./cypress/plugins/index.js')(on, config)
    },
  },
});
