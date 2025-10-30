/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class CheckoutSuccessPage {
    constructor(public expect) {}

    async assertThatOrderSuccessPageIsShown(page) {
        await this.expect(page).toHaveURL(/checkout\/onepage\/success/);

        const thankYouMessage = page.locator('text=Thank you for your purchase!');
        await this.expect(thankYouMessage).toBeVisible();
    }
}
