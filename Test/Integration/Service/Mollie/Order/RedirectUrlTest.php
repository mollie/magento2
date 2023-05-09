<?php

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Model\Methods\Directdebit;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\Order\RedirectUrl;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RedirectUrlTest extends IntegrationTestCase
{
    public function testReturnsTheUrlWhenOneIsSet(): void
    {
        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('startTransaction')->willReturn('https://example.com/');

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->get(RedirectUrl::class);
        $result = $instance->execute($mollieMock, $this->objectManager->create(OrderInterface::class));

        $this->assertEquals('https://example.com/', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/type 1
     * @return void
     */
    public function testRedirectDirectDebitToTheSuccessPageWhenInTestMode(): void
    {
        $mollieMock = $this->createMock(Directdebit::class);
        $mollieMock->method('startTransaction')->willReturn(''); // Should be empty

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->get(RedirectUrl::class);
        $result = $instance->execute($mollieMock, $this->objectManager->create(OrderInterface::class));

        $this->assertStringContainsString('checkout/onepage/success', $result);
    }

    public function testRedirectsToSuccessPageForApplePay(): void
    {
        $mollieMock = $this->createMock(ApplePay::class);
        $mollieMock->method('startTransaction')->willReturn(''); // Should be empty

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->get(RedirectUrl::class);
        $result = $instance->execute($mollieMock, $this->objectManager->create(OrderInterface::class));

        $this->assertStringContainsString('checkout/onepage/success', $result);
    }

    public function testRedirectsToSuccessPageForCreditCard(): void
    {
        $mollieMock = $this->createMock(Creditcard::class);
        $mollieMock->method('startTransaction')->willReturn(''); // Should be empty

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->get(RedirectUrl::class);
        $result = $instance->execute($mollieMock, $this->objectManager->create(OrderInterface::class));

        $this->assertStringContainsString('checkout/onepage/success', $result);
    }

    public function testRedirectsToTheCartWhenNoUrlIsAvailable(): void
    {
        $mollieMock = $this->createMock(Ideal::class);
        $mollieMock->method('startTransaction')->willReturn(null); // Should be empty

        /** @var ManagerInterface $messageManager */
        $messageManager = $this->objectManager->get(ManagerInterface::class);

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->get(RedirectUrl::class);
        $result = $instance->execute($mollieMock, $this->objectManager->create(OrderInterface::class));

        $this->assertSame(1, $messageManager->getMessages()->getCount());
        $this->assertEquals(
            'Something went wrong while trying to redirect you to Mollie. Please try again later.',
            $messageManager->getMessages()->getItemsByType('error')[0]->getText()
        );

        $this->assertStringContainsString('checkout/cart', $result);
    }
}
