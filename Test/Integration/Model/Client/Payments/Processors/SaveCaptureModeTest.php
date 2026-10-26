<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMode;
use Mollie\Payment\Model\Client\Payments\Processors\SaveCaptureMode;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;

class SaveCaptureModeTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItStoresTheCaptureModeOfTheMolliePayment(): void
    {
        $order = $this->loadOrder('100000001');

        $this->process($order, $this->molliePaymentWithCaptureMode(CaptureMode::AUTOMATIC));

        $this->assertSame(
            CaptureMode::AUTOMATIC,
            $order->getPayment()->getAdditionalInformation(CanUseManualCapture::CAPTURE_MODE),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItReplacesTheStoredCaptureModeWithTheOneMollieReports(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setAdditionalInformation(CanUseManualCapture::CAPTURE_MODE, CaptureMode::AUTOMATIC);

        $this->process($order, $this->molliePaymentWithCaptureMode(CaptureMode::MANUAL));

        $this->assertSame(
            CaptureMode::MANUAL,
            $order->getPayment()->getAdditionalInformation(CanUseManualCapture::CAPTURE_MODE),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItKeepsTheStoredCaptureModeWhenMollieReportsNone(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setAdditionalInformation(CanUseManualCapture::CAPTURE_MODE, CaptureMode::MANUAL);

        $this->process($order, $this->molliePaymentWithCaptureMode(null));

        $this->assertSame(
            CaptureMode::MANUAL,
            $order->getPayment()->getAdditionalInformation(CanUseManualCapture::CAPTURE_MODE),
        );
    }

    private function molliePaymentWithCaptureMode(?string $captureMode): Payment
    {
        /** @var MolliePaymentBuilder $paymentBuilder */
        $paymentBuilder = $this->objectManager->create(MolliePaymentBuilder::class);
        $molliePayment = $paymentBuilder->build();
        $molliePayment->captureMode = $captureMode;

        return $molliePayment;
    }

    private function process(OrderInterface $order, Payment $molliePayment): void
    {
        $this->objectManager->create(SaveCaptureMode::class)->process(
            $order,
            $molliePayment,
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'paid',
                'order_id' => $order->getEntityId(),
                'type' => 'webhook',
            ]),
        );
    }
}
