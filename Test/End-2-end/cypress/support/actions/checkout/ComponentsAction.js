export default class ComponentsAction {
  fillComponentsForm(cardHolder, cardNumber, expiryDate, verificationCode) {
    cy.getIframeBody('[name="cardHolder-input"]')
      .find('#cardHolder')
      .should('exist')
      .type(cardHolder)
    ;

    cy.getIframeBody('[name="cardNumber-input"]')
      .find('#cardNumber')
      .should('exist')
      .type(cardNumber)
    ;

    cy.getIframeBody('[name="expiryDate-input"]')
      .find('#expiryDate')
      .should('exist')
      .type(expiryDate)
    ;

    cy.getIframeBody('[name="verificationCode-input"]')
      .find('#verificationCode')
      .should('exist')
      .type(verificationCode)
    ;
  }
}
