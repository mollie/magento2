const origLog = Cypress.log;
Cypress.log = function (opts, ...other) {
  if (opts.url && opts.url.indexOf('/static/') !== -1) {
    return;
  }

  return origLog(opts, ...other);
};
