<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\Order\RefundUsingPayment;
use Mollie\Payment\Service\Order\ProcessAdjustmentFee;
use Mollie\Payment\Test\Fakes\Service\Mollie\Order\RefundUsingPaymentFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ProcessAdjustmentFeeTest extends IntegrationTestCase
{
    public function testRefundsPositive(): void
    {
        $refundUsingPayment = $this->objectManager->create(RefundUsingPaymentFake::class);
        $refundUsingPayment->disableParentCall();
        $this->objectManager->addSharedInstance($refundUsingPayment, RefundUsingPayment::class);

        $mollieApiClient = $this->objectManager->create(\Mollie\Api\MollieApiClient::class);

        $instance = $this->objectManager->create(ProcessAdjustmentFee::class);

        $order = $this->objectManager->create(OrderInterface::class);
        $creditmemo = $this->objectManager->create(CreditmemoInterface::class);
        $creditmemo->setAdjustment(10);

        $instance->handle($mollieApiClient, $order, $creditmemo);

        $this->assertFalse($instance->doNotRefundInMollie());
        $this->assertCount(1, $refundUsingPayment->getCalls());
    }

    public function testRefundsNegative(): void
    {
        $refundUsingPayment = $this->objectManager->create(RefundUsingPaymentFake::class);
        $refundUsingPayment->disableParentCall();
        $this->objectManager->addSharedInstance($refundUsingPayment, RefundUsingPayment::class);

        $mollieApiClient = $this->objectManager->create(\Mollie\Api\MollieApiClient::class);

        $instance = $this->objectManager->create(ProcessAdjustmentFee::class);

        $order = $this->objectManager->create(OrderInterface::class);
        $creditmemo = $this->objectManager->create(CreditmemoInterface::class);
        $creditmemo->setData('adjustment_negative', 10);

        $instance->handle($mollieApiClient, $order, $creditmemo);

        $this->assertTrue($instance->doNotRefundInMollie());
        $this->assertCount(1, $refundUsingPayment->getCalls());
    }

    public function testResetsTheRefundInMollieFlag(): void
    {
        // Scenario: Do a negative creditmemo first, then a creditmemo without adjustments.

        $refundUsingPayment = $this->objectManager->create(RefundUsingPaymentFake::class);
        $refundUsingPayment->disableParentCall();
        $this->objectManager->addSharedInstance($refundUsingPayment, RefundUsingPayment::class);

        $mollieApiClient = $this->objectManager->create(\Mollie\Api\MollieApiClient::class);

        $instance = $this->objectManager->create(ProcessAdjustmentFee::class);

        $negativeCreditmemo = $this->objectManager->create(CreditmemoInterface::class);
        $negativeCreditmemo->setData('adjustment_negative', 10);

        $order = $this->objectManager->create(OrderInterface::class);

        $instance->handle($mollieApiClient, $order, $negativeCreditmemo);
        $this->assertTrue($instance->doNotRefundInMollie());

        $creditmemoWithoutAdjustments = $this->objectManager->create(CreditmemoInterface::class);

        $instance->handle($mollieApiClient, $order, $creditmemoWithoutAdjustments);
        $this->assertFalse($instance->doNotRefundInMollie());
    }
}
