export default class Configuration {
    setValue(section, group, field, value) {
        cy.backendLogin();

        cy.get('[data-ui-id="menu-magento-config-system-config"] > a').then(element => {
            cy.visit(element.attr('href'));
        });

        cy.get('.mollie-tab').contains(section).click({force: true});

        cy.url().should('include', 'admin/system_config/edit/section/mollie_');

        cy.wait(1000);

        // Wait for JS to load
        cy.get('.mollie-tab._show', {timeout: 60000}).should('be.visible');

        cy.get('.entry-edit').contains(group).then(element => {
            if (!element.hasClass('open')) {
                cy.get(element).click();
            }
        });

        cy.contains('.entry-edit-head', group).parents('.section-config').within(element => {
            cy.contains('label', field)
                .parents('tr')
                .find(':input')
                .select(value, {force: true});
        })

        cy.get('#save').click();

        // Wait for JS to load
        cy.get('.mollie-tab').should('have.class', '_show');

        cy.contains('You saved the configuration.');
    }
}
