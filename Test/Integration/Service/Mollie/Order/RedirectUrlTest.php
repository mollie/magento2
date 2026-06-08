<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Model\Methods\Directdebit;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\Order\RedirectUrl;
use Mollie\Payment\Service\Mollie\StartTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RedirectUrlTest extends IntegrationTestCase
{
    public function testReturnsTheUrlWhenOneIsSet(): void
    {
        $method = $this->objectManager->get(Mollie::class);
        $startTransactionMock = $this->createMock(StartTransaction::class);
        $startTransactionMock->method('execute')->willReturn('https://example.com/');

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->create(RedirectUrl::class, ['startTransaction' => $startTransactionMock]);
        $result = $instance->execute($method, $this->objectManager->create(OrderInterface::class));

        $this->assertEquals('https://example.com/', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/type 1
     * @return void
     */
    public function testRedirectDirectDebitToTheSuccessPageWhenInTestMode(): void
    {
        $method = $this->objectManager->get(Directdebit::class);
        $startTransactionMock = $this->createMock(StartTransaction::class);
        $startTransactionMock->method('execute')->willReturn(''); // Should be empty

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->create(RedirectUrl::class, ['startTransaction' => $startTransactionMock]);
        $result = $instance->execute($method, $this->objectManager->create(OrderInterface::class));

        $this->assertStringContainsString('checkout/onepage/success', $result);
    }

    public function testRedirectsToSuccessPageForApplePay(): void
    {
        $method = $this->objectManager->get(ApplePay::class);
        $startTransactionMock = $this->createMock(StartTransaction::class);
        $startTransactionMock->method('execute')->willReturn(''); // Should be empty

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->create(RedirectUrl::class, ['startTransaction' => $startTransactionMock]);
        $result = $instance->execute($method, $this->objectManager->create(OrderInterface::class));

        $this->assertStringContainsString('checkout/onepage/success', $result);
    }

    public function testRedirectsToSuccessPageForCreditCard(): void
    {
        $method = $this->objectManager->get(Creditcard::class);
        $startTransactionMock = $this->createMock(StartTransaction::class);
        $startTransactionMock->method('execute')->willReturn(''); // Should be empty

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->create(RedirectUrl::class, ['startTransaction' => $startTransactionMock]);
        $result = $instance->execute($method, $this->objectManager->create(OrderInterface::class));

        $this->assertStringContainsString('checkout/onepage/success', $result);
    }

    public function testRedirectsToTheCartWhenNoUrlIsAvailable(): void
    {
        $method = $this->objectManager->get(Ideal::class);
        $startTransactionMock = $this->createMock(StartTransaction::class);
        $startTransactionMock->method('execute')->willReturn(null); // Should be empty

        /** @var ManagerInterface $messageManager */
        $messageManager = $this->objectManager->get(ManagerInterface::class);

        /** @var RedirectUrl $instance */
        $instance = $this->objectManager->create(RedirectUrl::class, ['startTransaction' => $startTransactionMock]);
        $result = $instance->execute($method, $this->objectManager->create(OrderInterface::class));

        $this->assertSame(1, $messageManager->getMessages()->getCount());
        $this->assertEquals(
            'Something went wrong while trying to redirect you to Mollie. Please try again later.',
            $messageManager->getMessages()->getItemsByType('error')[0]->getText(),
        );

        $this->assertStringContainsString('checkout/cart', $result);
    }
}
