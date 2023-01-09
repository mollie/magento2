export default class Cookies {
  disableSameSiteCookieRestrictions() {
    cy.intercept('*', (req) => {
      req.on('response', (res) => {
        if (!res.headers['set-cookie']) {
          return;
        }

        const disableSameSite = (headerContent) => {
          return headerContent.replace(/samesite=(lax|strict)/ig, 'samesite=none');
        }

        if (Array.isArray(res.headers['set-cookie'])) {
          res.headers['set-cookie'] = res.headers['set-cookie'].map(disableSameSite);
        } else {
          res.headers['set-cookie'] = disableSameSite(res.headers['set-cookie']);
        }
      })
    });
  }
}
