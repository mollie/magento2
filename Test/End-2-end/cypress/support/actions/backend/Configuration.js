export default class Configuration {
    setValue(section, group, field, value) {
        cy.backendLogin();

        cy.get('[data-ui-id="menu-magento-config-system-config"] > a').then(element => {
            cy.visit(element.attr('href'));
        });

        cy.contains('Currency Setup').should('be.visible');

        // When this is not visible, the page is not loaded yet.
        cy.get('#system_config_tabs .mollie-tab').click();

        cy.get('.mollie-tab').contains(section).click();

        // Wait for JS to load
        cy.get('.mollie-tab').should('have.class', '_show');

        cy.get('.entry-edit').contains(group).then(element => {
            if (!element.hasClass('open')) {
                cy.get(element).click();
            }
        });

        cy.get('label').contains(field).parents('tr').find(':input').select(value);

        cy.get('#save').click();

        // Wait for JS to load
        cy.get('.mollie-tab').should('have.class', '_show');

        cy.contains('You saved the configuration.');
    }
}
