const origLog = Cypress.log;
Cypress.log = function (opts, ...other) {
  if (opts.url && opts.url.indexOf('/static/') !== -1) {
    return;
  }

  return origLog(opts, ...other);
};

Cypress.Commands.add('backendLogin', () => {
  const username = 'exampleuser';
  const password = 'examplepassword123';

  cy.session([username, password], () => {
    cy.visit('/admin');
    cy.get('#username').type(username);
    cy.get('#login').type(password);
    cy.get('.action-login').click();

    cy.url().should('include', '/admin/admin/dashboard');
  });

  cy.visit('/admin/admin/dashboard');
});

Cypress.Commands.add('getIframeBody', (selector) => {
    // get the iframe > document > body
    // and retry until the body element is not empty
    return cy
        .get('iframe' + selector)
        .its('0.contentDocument.body').should('not.be.empty')
        // wraps "body" DOM element to allow
        // chaining more Cypress commands, like ".find(...)"
        // https://on.cypress.io/wrap
        .then(cy.wrap)
})
