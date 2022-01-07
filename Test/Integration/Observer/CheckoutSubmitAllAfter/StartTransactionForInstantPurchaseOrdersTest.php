<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Observer\CheckoutSubmitAllAfter;

use Magento\Framework\Event\Observer;
use Magento\InstantPurchase\Model\QuoteManagement\PaymentConfiguration;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Observer\CheckoutSubmitAllAfter\StartTransactionForInstantPurchaseOrders;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class StartTransactionForInstantPurchaseOrdersTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCallsTheStartTransactionMethod()
    {
        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->expects($this->once())->method('startTransaction');

        $order = $this->loadOrderById('100000001');

        // Method must be "mollie_methods_creditcard_vault"
        $order->getPayment()->setMethod(CreditcardVault::CODE);

        // Additional information "instant-purchase" must be "true".
        $order->getPayment()->setAdditionalInformation(PaymentConfiguration::MARKER, 'true');

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $order);

        /** @var StartTransactionForInstantPurchaseOrders $instance */
        $instance = $this->objectManager->create(StartTransactionForInstantPurchaseOrders::class, [
            'mollie' => $mollieMock,
        ]);

        $instance->execute($observer);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDoesNothingWhenTheOrderIsNotPresent()
    {
        // Create observer but don't add an order.
        $observer = $this->objectManager->create(Observer::class);

        /** @var StartTransactionForInstantPurchaseOrders $instance */
        $instance = $this->objectManager->create(StartTransactionForInstantPurchaseOrders::class);

        $instance->execute($observer);
    }
}
