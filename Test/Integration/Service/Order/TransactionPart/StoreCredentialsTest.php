<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Customer\Model\Session;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\TransactionPart\StoreCredentials;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class StoreCredentialsTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenPaymentMethodIsNotCreditcard(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_ideal');

        $instance = $this->createInstance(true, true);
        $result = $instance->process($order, []);

        $this->assertEquals([], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenCustomerIsNotLoggedIn(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');
        $order->getPayment()->setAdditionalInformation('mollie_save_card', true);

        $instance = $this->createInstance(false, true);
        $result = $instance->process($order, []);

        $this->assertEquals([], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenToggleIsOff(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');
        $order->getPayment()->setAdditionalInformation('mollie_save_card', true);

        $instance = $this->createInstance(true, false);
        $result = $instance->process($order, []);

        $this->assertEquals([], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenSaveCardFlagIsMissing(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');

        $instance = $this->createInstance(true, true);
        $result = $instance->process($order, []);

        $this->assertEquals([], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenMandateIdIsAlreadySet(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');
        $order->getPayment()->setAdditionalInformation('mollie_save_card', true);
        $order->getPayment()->setAdditionalInformation('mollie_mandate_id', 'mdt_abc123');

        $instance = $this->createInstance(true, true);
        $result = $instance->process($order, []);

        $this->assertEquals([], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsStoreCredentials(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');
        $order->getPayment()->setAdditionalInformation('mollie_save_card', true);

        $instance = $this->createInstance(true, true);
        $result = $instance->process($order, []);

        $this->assertEquals(['storeCredentials' => true], $result);
    }

    private function createInstance(bool $loggedIn, bool $toggleEnabled): StoreCredentials
    {
        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('isLoggedIn')->willReturn($loggedIn);

        $configMock = $this->createMock(Config::class);
        $configMock->method('creditcardEnableCustomersApi')->willReturn($toggleEnabled);

        return $this->objectManager->create(StoreCredentials::class, [
            'customerSession' => $sessionMock,
            'config' => $configMock,
        ]);
    }
}
