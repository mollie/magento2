<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Savedcards;

use Magento\Customer\Model\Session;
use Magento\TestFramework\TestCase\AbstractController;

class IndexTest extends AbstractController
{
    public function testGuestIsRedirectedToLoginInsteadOfReceivingAnError(): void
    {
        $this->dispatch('mollie/savedcards/index');

        $this->assertRedirect($this->stringContains('customer/account/login'));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testLoggedInCustomerDoesNotReceiveAnError(): void
    {
        $this->_objectManager->get(Session::class)->setCustomerId(1);

        $this->dispatch('mollie/savedcards/index');

        $this->assertRedirect($this->stringContains('noroute'));
    }
}
