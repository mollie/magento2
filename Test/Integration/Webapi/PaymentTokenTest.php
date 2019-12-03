<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Webapi;

use Magento\Quote\Model\Quote;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentTokenTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testAddsANewPaymentToken()
    {
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        /** @var PaymentToken $instance */
        $instance = $this->objectManager->create(PaymentToken::class);
        $token = $instance->generate($cart);

        $model = $this->objectManager->create(PaymentTokenRepositoryInterface::class)->getByToken($token);

        $this->assertEquals($token, $model->getToken());
    }
}
