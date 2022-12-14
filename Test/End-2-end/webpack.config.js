var path = require('path')

module.exports = {
   resolve: {
       alias: {
           CompositeActions: path.resolve(__dirname, 'cypress/support/actions/composite'),
           Pages: path.resolve(__dirname, 'cypress/support/pages'),
           Services: path.resolve(__dirname, 'cypress/support/services'),
           Fixtures: path.resolve(__dirname, 'cypress/fixtures'),
           Plugins: path.resolve(__dirname, 'cypress/plugins')
       }
   }
}
