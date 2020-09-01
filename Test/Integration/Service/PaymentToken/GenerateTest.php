<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\PaymentToken;

use Magento\Quote\Model\Quote;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\PaymentToken\Generate;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GenerateTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testGeneratesTokenForOrder()
    {
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        $order = $this->loadOrder('100000001');
        $order->setQuoteId($cart->getId());

        /** @var PaymentTokenRepositoryInterface $repository */
        $repository = $this->objectManager->create(PaymentTokenRepositoryInterface::class);
        $token = $repository->getByOrder($order);
        $this->assertNull($token);

        /** @var Generate $instance */
        $instance = $this->objectManager->create(Generate::class);
        $instance->forOrder($order);

        /** @var PaymentTokenRepositoryInterface $repository */
        $repository = $this->objectManager->create(PaymentTokenRepositoryInterface::class);
        $token = $repository->getByOrder($order);

        $this->assertNotNull($token);
    }
}
