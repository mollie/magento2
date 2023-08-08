const fs = require('fs');
const TestRailReporter = require("cypress-testrail");

/**
 * When the tests are run in CI, videos are generated for each test. Compressing these videos takes a lot of time,
 * and for successfull tests, the videos are not needed. This function deletes the videos for successfull tests.
 *
 * @param on
 * @param config
 */
module.exports = (on, config) => {
    // Cypress does not allow to register multiple `after:spec` hooks. So call the _afterSpec method
    // within our own hook.
    const testRailReporter = new TestRailReporter(on, config, 'Magento');
    testRailReporter.register();

    // Source: https://github.com/elgentos/magento2-cypress-testing-suite/blob/main/cypress.config.js#L42-L56
    on('after:spec', (spec, results) => {
        testRailReporter._afterSpec(spec, results);

        // If a retry failed, save the video, otherwise delete it to save time by not compressing it.
        if (results && results.video) {
            // Do we have failures for any retry attempts?
            const failures = results.tests.find(test => {
                return test.attempts.find(attempt => {
                    return attempt.state === 'failed'
                })
            });

            // Delete the video if the spec passed on all attempts
            if (!failures) {
                fs.existsSync(results.video) && fs.unlinkSync(results.video)
            }
        }
    })
};
