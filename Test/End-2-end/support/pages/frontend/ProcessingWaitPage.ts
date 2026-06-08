/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class ProcessingWaitPage {
    constructor(public expect) {}

    async assertThatWaitPageIsShown(page) {
        await this.expect(page).toHaveURL(/mollie\/checkout\/processingwait/);
        await this.expect(page.locator('#mollie-order-status')).toBeVisible();
    }
}
