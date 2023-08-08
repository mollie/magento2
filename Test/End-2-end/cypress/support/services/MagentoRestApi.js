export default class MagentoRestApi {

    getInvoicesByOrderId(orderId) {
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

    createCustomer(email) {
        const data = {
            'customer': {
                'email': email || 'user@example.com',
                'firstname': 'John',
                'lastname': 'Doe',
                'storeId': 1,
                'websiteId': 1,
                'group_id': 3, // 3 = Retrailer
            },
            'password': 'Password1234'
        }

        return cy.request({
            method: 'POST',
            url: '/rest/all/V1/customers',
            headers: {
                'accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + Cypress.env('admin_token'),
            },
            body: JSON.stringify(data)
        }).then(response => response.body);
    }
}
