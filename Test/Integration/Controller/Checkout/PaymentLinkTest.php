<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Checkout;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Payment\Model\Mollie;

class PaymentLinkTest extends AbstractController
{
    public function testThrowErrorWhenOrderIsNotSet(): void
    {
        $this->dispatch('mollie/checkout/paymentLink');

        $this->assertSame(400, $this->getResponse()->getHttpResponseCode());
    }

    public function testThrowsErrorWhenDecodingIsEmpty(): void
    {
        $this->dispatch('mollie/checkout/paymentLink/order/999');

        $this->assertSame(404, $this->getResponse()->getHttpResponseCode());
    }

    public function testThrowsErrorWhenOrderIsInvalid(): void
    {
        // OTk5 = an order id (999) but encrypted
        $this->dispatch('mollie/checkout/paymentLink/order/OTk5');

        $this->assertSame(404, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testRedirectsToMollieWhenTheInputIsValid(): void
    {
        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('startTransaction')->willReturn('https://www.example.com');
        $this->_objectManager->addSharedInstance($mollieMock, Mollie::class);

        $order = $this->_objectManager->create(Order::class)->loadByIncrementId('100000001');
        $key = $this->_objectManager->get(EncryptorInterface::class)->encrypt($order->getId());

        $this->dispatch('mollie/checkout/paymentLink/order/' . base64_encode($key));

        $this->assertSame(302, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testRedirectsToTheHomepageWhenAlreadyPaid(): void
    {
        $order = $this->_objectManager->create(Order::class)->loadByIncrementId('100000001');
        $order->setState(Order::STATE_PROCESSING);

        $key = $this->_objectManager->get(EncryptorInterface::class)->encrypt($order->getId());

        $this->dispatch('mollie/checkout/paymentLink/order/' . base64_encode($key));

        $response = $this->getResponse();
        $this->assertSame(302, $response->getHttpResponseCode());
        $this->assertSame('/', $response->getHeader('Location')->getUri());
    }
}
