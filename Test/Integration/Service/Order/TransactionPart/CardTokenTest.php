<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Mollie\Payment\Service\Order\TransactionPart\CardToken;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CardTokenTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsDataForPaymentsApi(): void
    {
        $order = $this->loadOrderById('100000001');
        $payment = $order->getPayment();
        $payment->setMethod('mollie_methods_creditcard');
        $payment->setAdditionalInformation('card_token', 'abc123');

        /** @var CardToken $instance */
        $instance = $this->objectManager->create(CardToken::class);

        $transaction = $instance->process($order, ['method' => 'creditcard']);

        $this->assertEquals(['method' => 'creditcard', 'additional' => ['cardToken' => 'abc123']], $transaction);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotChangeTheTransactionWhenNoCardTokenIsPresent(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');

        /** @var CardToken $instance */
        $instance = $this->objectManager->create(CardToken::class);

        $transaction = $instance->process($order, ['method' => 'creditcard']);

        $this->assertEquals(['method' => 'creditcard'], $transaction);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesATheCardFieldWhenThePaymentMethodIsNotCreditcard(): void
    {
        $order = $this->loadOrderById('100000001');
        $payment = $order->getPayment();
        $payment->setMethod('checkmo');
        $payment->setAdditionalInformation('card_token', 'abc123');

        /** @var CardToken $instance */
        $instance = $this->objectManager->create(CardToken::class);

        $transaction = $instance->process($order, ['method' => 'creditcard']);

        $this->assertEquals(['method' => 'creditcard'], $transaction);
    }
}
