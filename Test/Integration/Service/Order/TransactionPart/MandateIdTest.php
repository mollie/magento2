<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\TransactionPart\MandateId;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MandateIdTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenToggleIsOff(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setAdditionalInformation('mollie_mandate_id', 'mdt_abc123');

        $instance = $this->createInstance(false);
        $result = $instance->process($order, []);

        $this->assertEquals([], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenMandateIdIsMissing(): void
    {
        $order = $this->loadOrderById('100000001');

        $instance = $this->createInstance(true);
        $result = $instance->process($order, []);

        $this->assertEquals([], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsMandateId(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setAdditionalInformation('mollie_mandate_id', 'mdt_abc123');

        $instance = $this->createInstance(true);
        $result = $instance->process($order, []);

        $this->assertEquals(['mandateId' => 'mdt_abc123'], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCardTokenIsNotAddedWhenMandateIdIsSet(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');
        $order->getPayment()->setAdditionalInformation('mollie_mandate_id', 'mdt_abc123');
        $order->getPayment()->setAdditionalInformation('card_token', 'tkn_xyz');

        $configMock = $this->createMock(Config::class);
        $configMock->method('creditcardEnableCustomersApi')->willReturn(true);

        $mandateIdPart = $this->objectManager->create(MandateId::class, ['config' => $configMock]);
        $cardTokenPart = $this->objectManager->create(\Mollie\Payment\Service\Order\TransactionPart\CardToken::class);

        $transaction = $mandateIdPart->process($order, []);
        $transaction = $cardTokenPart->process($order, $transaction);

        $this->assertArrayHasKey('mandateId', $transaction);
        $this->assertArrayNotHasKey('additional', $transaction);
    }

    private function createInstance(bool $toggleEnabled): MandateId
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method('creditcardEnableCustomersApi')->willReturn($toggleEnabled);

        return $this->objectManager->create(MandateId::class, [
            'config' => $configMock,
        ]);
    }
}
