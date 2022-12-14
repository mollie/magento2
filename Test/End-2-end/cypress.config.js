const { defineConfig } = require("cypress");

module.exports = defineConfig({
  projectId: "44bnds",
  chromeWebSecurity: false,
  e2e: {
    experimentalWebKitSupport: true,
    setupNodeEvents(on, config) {
      return require('./cypress/plugins/index.js')(on, config)
    },
  },
});
