<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\PaymentToken;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\PaymentToken\Generate;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForOrder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentTokenForOrderTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsAnExistingToken()
    {
        $order = $this->getOrder();

        /** @var Generate $generate */
        $generate = $this->objectManager->create(Generate::class);
        $tokenForOrder = $generate->forOrder($order);

        /** @var PaymentTokenForOrder $instance */
        $instance = $this->objectManager->create(PaymentTokenForOrder::class);
        $result = $instance->execute($order);

        $this->assertTrue(is_string($result));
        $this->assertEquals($tokenForOrder->getToken(), $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCreatesANewTokenWhenNotAlreadyAvailable()
    {
        $order = $this->getOrder();

        /** @var SearchCriteriaBuilder $criteriaBuilder */
        $criteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);

        /** @var PaymentTokenRepositoryInterface $repository */
        $repository = $this->objectManager->create(PaymentTokenRepositoryInterface::class);
        $items = $repository->getList($criteriaBuilder->create());

        foreach ($items->getItems() as $item) {
            $repository->delete($item);
        }

        /** @var PaymentTokenForOrder $instance */
        $instance = $this->objectManager->create(PaymentTokenForOrder::class);
        $result = $instance->execute($order);

        $this->assertTrue(is_string($result));
        $this->assertFalse(empty($result));
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    private function getOrder(): \Magento\Sales\Api\Data\OrderInterface
    {
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        $order = $this->loadOrder('100000001');
        $order->setQuoteId($cart->getId());
        return $order;
    }
}
