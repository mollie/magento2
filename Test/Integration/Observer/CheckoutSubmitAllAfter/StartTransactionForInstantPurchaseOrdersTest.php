<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\CheckoutSubmitAllAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Module\Manager;
use Magento\InstantPurchase\Model\QuoteManagement\PaymentConfiguration;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Observer\CheckoutSubmitAllAfter\StartTransactionForInstantPurchaseOrders;
use Mollie\Payment\Service\Mollie\StartTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class StartTransactionForInstantPurchaseOrdersTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var Manager $moduleManager */
        $moduleManager = $this->objectManager->get(Manager::class);
        if (!$moduleManager->isEnabled('Magento_InstantPurchase')) {
            $this->markTestSkipped('Module Magento_InstantPurchase is not enabled');
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCallsTheStartTransactionMethod(): void
    {
        $startTransactionMock = $this->createMock(StartTransaction::class);
        $startTransactionMock->expects($this->once())->method('execute');

        $order = $this->loadOrderById('100000001');

        // Method must be "mollie_methods_creditcard_vault"
        $order->getPayment()->setMethod(CreditcardVault::CODE);

        // Additional information "instant-purchase" must be "true".
        $order->getPayment()->setAdditionalInformation(PaymentConfiguration::MARKER, 'true');

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $order);

        /** @var StartTransactionForInstantPurchaseOrders $instance */
        $instance = $this->objectManager->create(StartTransactionForInstantPurchaseOrders::class, [
            'startTransaction' => $startTransactionMock,
        ]);

        $instance->execute($observer);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDoesNothingWhenTheOrderIsNotPresent(): void
    {
        // Create observer but don't add an order.
        $observer = $this->objectManager->create(Observer::class);

        /** @var StartTransactionForInstantPurchaseOrders $instance */
        $instance = $this->objectManager->create(StartTransactionForInstantPurchaseOrders::class);

        $instance->execute($observer);
    }
}
