<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMode;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CanUseManualCaptureTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testItFallsBackToTheConfigurationWhenNoCaptureModeIsStored(): void
    {
        $order = $this->creditcardOrder();

        $this->assertTrue($this->createInstance()->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testItIgnoresTheConfigurationWhenThePaymentWasCreatedWithAutomaticCapture(): void
    {
        $order = $this->creditcardOrder();
        $order->getPayment()->setAdditionalInformation(CanUseManualCapture::CAPTURE_MODE, CaptureMode::AUTOMATIC);

        $this->assertFalse($this->createInstance()->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode automatic
     */
    public function testItIgnoresTheConfigurationWhenThePaymentWasCreatedWithManualCapture(): void
    {
        $order = $this->creditcardOrder();
        $order->getPayment()->setAdditionalInformation(CanUseManualCapture::CAPTURE_MODE, CaptureMode::MANUAL);

        $this->assertTrue($this->createInstance()->execute($order));
    }

    private function creditcardOrder(): OrderInterface
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        return $order;
    }

    private function createInstance(): CanUseManualCapture
    {
        return $this->objectManager->create(CanUseManualCapture::class);
    }
}
