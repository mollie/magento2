<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\TransactionDescription;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TransactionDescriptionTest extends IntegrationTestCase
{
    public function returnsTheCorrectDescriptionForRegularTransactionsProvider(): array
    {
        return [
            ['{ordernumber}', '0000025'],
            ['', '0000025'],
            ['{storename}', 'My Test Store'],
            ['{storename}: {ordernumber}', 'My Test Store: 0000025'],
            ['Order {ordernumber} from this store', 'Order 0000025 from this store'],
            ['Order {ordernumber} for customer {customerid}', 'Order 0000025 for customer 999'],
        ];
    }

    /**
     * @magentoConfigFixture current_store general/store_information/name My Test Store
     * @dataProvider returnsTheCorrectDescriptionForRegularTransactionsProvider
     */
    public function testReturnsTheCorrectDescriptionForRegularTransactions(string $description, string $expected): void
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method('paymentMethodDescription')->willReturn($description);

        /** @var TransactionDescription $instance */
        $instance = $this->objectManager->create(TransactionDescription::class, [
            'config' => $configMock,
        ]);

        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal');

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setPayment($payment);
        $order->setIncrementId('0000025');
        $order->setStoreId(1);
        $order->setCustomerId(999);

        $result = $instance->forRegularTransaction($order);

        $this->assertSame($expected, $result);
    }
}
