<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMoment;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Service\Mollie\Order\WhenToCapture;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class WhenToCaptureTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/when_to_capture shipment
     */
    public function testItFallsBackToTheConfigurationWhenNoMomentIsStored(): void
    {
        $order = $this->creditcardOrder();

        $instance = $this->createInstance();

        $this->assertTrue($instance->onShipment($order));
        $this->assertFalse($instance->onInvoice($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/when_to_capture shipment
     */
    public function testItUsesTheMomentStoredWhenThePaymentWasCreated(): void
    {
        $order = $this->creditcardOrder();
        $order->getPayment()->setAdditionalInformation(WhenToCapture::CAPTURE_MOMENT, CaptureMoment::ON_INVOICE);

        $instance = $this->createInstance();

        $this->assertTrue($instance->onInvoice($order));
        $this->assertFalse($instance->onShipment($order));
    }

    private function creditcardOrder(): OrderInterface
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        return $order;
    }

    private function createInstance(): WhenToCapture
    {
        return $this->objectManager->create(WhenToCapture::class);
    }
}
