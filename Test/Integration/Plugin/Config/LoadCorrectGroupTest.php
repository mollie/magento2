<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Plugin\Config;

use Magento\Config\Model\Config\Loader;
use Mollie\Payment\Plugin\Config\LoadCorrectGroup;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LoadCorrectGroupTest extends IntegrationTestCase
{
    public function returnsCorrectGroupProvider()
    {
        return [
            ['mollie_second_chance_email'],
            ['mollie_advanced'],
            ['mollie_payment_methods'],
            ['mollie_general'],
        ];
    }

    /**
     * @dataProvider returnsCorrectGroupProvider
     * @param string $group
     */
    public function testReturnsCorrectGroup($group)
    {
        /** @var LoadCorrectGroup $instance */
        $instance = $this->objectManager->create(LoadCorrectGroup::class);
        $result = $instance->beforeGetConfigByPath($this->objectManager->get(Loader::class), $group, null, null, null);

        $this->assertEquals('payment', $result[0]);
    }
}