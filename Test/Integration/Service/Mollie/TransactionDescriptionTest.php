<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\TransactionDescription;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TransactionDescriptionTest extends IntegrationTestCase
{
    public function returnsTheCorrectDescriptionForRegularTransactionsProvider()
    {
        return [
            ['{ordernumber}', '0000025'],
            ['', '0000025'],
            ['{storename}', 'My Test Store'],
            ['{storename}: {ordernumber}', 'My Test Store: 0000025'],
            ['Order {ordernumber} from this store', 'Order 0000025 from this store'],
        ];
    }

    /**
     * @magentoConfigFixture current_store general/store_information/name My Test Store
     * @dataProvider returnsTheCorrectDescriptionForRegularTransactionsProvider
     */
    public function testReturnsTheCorrectDescriptionForRegularTransactions($description, $expected)
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method('paymentMethodDescription')->willReturn($description);

        /** @var TransactionDescription $instance */
        $instance = $this->objectManager->create(TransactionDescription::class, [
            'config' => $configMock,
        ]);

        $result = $instance->forRegularTransaction('ideal', '0000025', 1);

        $this->assertSame($expected, $result);
    }
}
