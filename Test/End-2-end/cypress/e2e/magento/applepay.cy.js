/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

describe('Apple Pay', () => {
  it('C2033291: Validate that the Apple Pay Develop Merchantid Domain Association file can be loaded', () => {
    cy.request('/.well-known/apple-developer-merchantid-domain-association')
      .then((response) => {
        expect(response.body).to.satisfy(body => body.startsWith('7B2270737'));
        expect(response.body.trim()).to.satisfy(body => body.endsWith('837303533303738636562626638326462306561376633303030303030303030303030227D'));
      });
  });
});
