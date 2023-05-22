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
      require('./cypress/plugins/index.js')(on, config);

      const mollie_available_methods = new Promise((resolve, reject) => {
          var https = require('follow-redirects').https;
          var fs = require('fs');

          const query = `
          query {
             molliePaymentMethods(input:{amount:10, currency:"EUR"}) {
               methods {
                 code
                 image
                 name
               }
             }
            }
          `;

          var options = {
              'method': 'GET',
              'hostname': 'mollie-opensource-237.controlaltdelete.dev',
              'path': '/graphql?query=' + encodeURIComponent(query),
              'headers': {
                  'Content-Type': 'application/json',
                  'Cookie': 'XDEBUG_SESSION=PHPSTORMx'
              },
              'maxRedirects': 20
          };

          var req = https.request(options, function (res) {
              var chunks = [];

              res.on("data", function (chunk) {
                  chunks.push(chunk);
              });

              res.on("end", function (chunk) {
                  var body = Buffer.concat(chunks);
                  const methods = JSON.parse(body.toString()).data.molliePaymentMethods.methods.map(data => {
                      return data.code
                  })

                  config.env.mollie_available_methods = methods;

                  console.log('Available Mollie payment methods: ', methods);

                  resolve(config);
              });

              res.on("error", function (error) {
                  console.error('Error while fetching Mollie Payment methods', error);
                  reject(error);
              });
          });

          var postData = JSON.stringify({
              query: '',
              variables: {}
          });

          req.end();
      });

      return mollie_available_methods;
    },
  },
});
