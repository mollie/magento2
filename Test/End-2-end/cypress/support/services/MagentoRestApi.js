export default class MagentoRestApi {

    getInvoicesByOrderId(orderId) {
        cy.log('bearer', Cypress.env('admin_token'))

        return cy.request({
            method: 'GET',
            url: '/rest/all/V1/invoices?searchCriteria[filter_groups][0][filters][0][field]=order_id&searchCriteria[filter_groups][0][filters][0][value]=' + orderId,
            headers: {
                'accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + Cypress.env('admin_token'),
            }
        }).then(response => response.body);
    }
}
