<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Service\Mollie\Order\IsPaymentAlreadyProcessed;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class IsPaymentAlreadyProcessedTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItReturnsFalseWhenThePaymentWasNeverProcessed(): void
    {
        $order = $this->loadOrder('100000001');

        $this->assertFalse($this->createInstance()->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItReturnsTrueWhenTheProcessedFlagIsSet(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setAdditionalInformation(IsPaymentAlreadyProcessed::PAYMENT_PROCESSED, true);

        $this->assertTrue($this->createInstance()->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItReturnsTrueWhenTheTransactionIsClosedWithinTheSameRequest(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setIsTransactionClosed(true);

        $this->assertTrue($this->createInstance()->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testItReturnsTrueForOrdersProcessedBeforeThisFlagExisted(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setAdditionalInformation(IsPaymentAlreadyProcessed::STATUS_UPDATED, 1);

        $this->assertGreaterThan(0, $order->getInvoiceCollection()->count());
        $this->assertTrue($this->createInstance()->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItReturnsFalseForLegacyOrdersWithoutAnInvoice(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setAdditionalInformation(IsPaymentAlreadyProcessed::STATUS_UPDATED, 1);

        $this->assertFalse($this->createInstance()->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItReturnsFalseWhenTheMethodUsesManualCapture(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setAdditionalInformation(IsPaymentAlreadyProcessed::PAYMENT_PROCESSED, true);

        $this->assertFalse($this->createInstance(true)->execute($order));
    }

    private function createInstance(bool $usesManualCapture = false): IsPaymentAlreadyProcessed
    {
        $canUseManualCapture = $this->createMock(CanUseManualCapture::class);
        $canUseManualCapture->method('execute')->willReturn($usesManualCapture);

        return $this->objectManager->create(IsPaymentAlreadyProcessed::class, [
            'canUseManualCapture' => $canUseManualCapture,
        ]);
    }
}
