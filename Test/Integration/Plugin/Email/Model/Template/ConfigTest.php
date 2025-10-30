<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Plugin\Email\Model\Template;

use Mollie\Payment\Plugin\Email\Model\Template\Config;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ConfigTest extends IntegrationTestCase
{
    public function replacesAllValues(): array
    {
        return [
            ['payment_mollie_mollie_general_second_chance_email_template'],
            ['payment_us_mollie_mollie_general_second_chance_email_template'],
            ['payment_fr_mollie_mollie_general_second_chance_email_template'],
        ];
    }

    /**
     * @param $input
     * @dataProvider replacesAllValues
     */
    public function testReplacesAllValues(string $input): void
    {
        /** @var Config $instance */
        $instance = $this->objectManager->create(Config::class);

        $subject = $this->objectManager->create(\Magento\Email\Model\Template\Config::class);

        $this->assertEquals(
            'payment_other_mollie_mollie_general_second_chance_email_template',
            $instance->beforeGetTemplateLabel($subject, $input)[0],
        );
    }
}
