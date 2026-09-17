<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\SalesModelServiceQuoteSubmitSuccess;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess\AttachPaymentTokenToOrder;
use Mollie\Payment\Service\PaymentToken\Generate;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class AttachPaymentTokenToOrderTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testAttachesATokenThatHasNoOrderYet(): void
    {
        $quote = $this->getQuote();
        $order = $this->getOrderOnQuote('100000001', $quote);

        /** @var Generate $generate */
        $generate = $this->objectManager->create(Generate::class);
        $token = $generate->forCart($quote);

        $this->dispatchObserver($order, $quote);

        $this->assertEquals(
            $order->getEntityId(),
            $this->getRepository()->getByToken($token)->getOrderId(),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testKeepsTheTokenOfAnOrderThatWasAlreadyPlacedOnThisQuote(): void
    {
        $quote = $this->getQuote();
        $firstOrder = $this->getOrderOnQuote('100000001', $quote);
        $secondOrder = $this->getOrderOnQuote('100000002', $quote);

        /** @var Generate $generate */
        $generate = $this->objectManager->create(Generate::class);
        $token = $generate->forOrder($firstOrder)->getToken();

        $this->dispatchObserver($secondOrder, $quote);

        $this->assertEquals(
            $firstOrder->getEntityId(),
            $this->getRepository()->getByToken($token)->getOrderId(),
            'The token of the first order was taken over by the second order',
        );

        $this->assertNotNull(
            $this->getRepository()->getByOrder($firstOrder),
            'The first order can no longer validate the return from Mollie',
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testOnlyAttachesTheTokenThatHasNoOrderYet(): void
    {
        $quote = $this->getQuote();
        $firstOrder = $this->getOrderOnQuote('100000001', $quote);
        $secondOrder = $this->getOrderOnQuote('100000002', $quote);

        /** @var Generate $generate */
        $generate = $this->objectManager->create(Generate::class);
        $tokenOfFirstOrder = $generate->forOrder($firstOrder)->getToken();
        $unassignedToken = $generate->forCart($quote);

        $this->dispatchObserver($secondOrder, $quote);

        $this->assertEquals(
            $firstOrder->getEntityId(),
            $this->getRepository()->getByToken($tokenOfFirstOrder)->getOrderId(),
        );

        $this->assertEquals(
            $secondOrder->getEntityId(),
            $this->getRepository()->getByToken($unassignedToken)->getOrderId(),
        );
    }

    private function dispatchObserver(OrderInterface $order, Quote $quote): void
    {
        $event = $this->objectManager->create(Event::class, [
            'data' => ['order' => $order, 'quote' => $quote],
        ]);

        $observer = $this->objectManager->create(Observer::class, [
            'data' => ['event' => $event],
        ]);

        $this->objectManager->create(AttachPaymentTokenToOrder::class)->execute($observer);
    }

    private function getRepository(): PaymentTokenRepositoryInterface
    {
        return $this->objectManager->create(PaymentTokenRepositoryInterface::class);
    }

    private function getQuote(): Quote
    {
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        return $quote;
    }

    private function getOrderOnQuote(string $incrementId, Quote $quote): OrderInterface
    {
        $order = $this->loadOrder($incrementId);
        $order->setQuoteId($quote->getId());

        return $order;
    }
}
