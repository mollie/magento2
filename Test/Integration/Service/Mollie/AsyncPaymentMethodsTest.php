<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Service\Mollie\AsyncPaymentMethods;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AsyncPaymentMethodsTest extends IntegrationTestCase
{
    /**
     * @dataProvider containsProvider
     */
    #[DataProvider('containsProvider')]
    public function testContains(?string $method, bool $expected): void
    {
        /** @var AsyncPaymentMethods $instance */
        $instance = $this->objectManager->get(AsyncPaymentMethods::class);

        $this->assertEquals($expected, $instance->contains($method));
    }

    public static function containsProvider(): array
    {
        return [
            'banktransfer' => ['banktransfer', true],
            'mollie_methods_banktransfer' => ['mollie_methods_banktransfer', true],
            'paybybank' => ['paybybank', true],
            'mollie_methods_paybybank' => ['mollie_methods_paybybank', true],
            'ideal — not async' => ['ideal', false],
            'mollie_methods_ideal — not async' => ['mollie_methods_ideal', false],
            'null' => [null, false],
        ];
    }
}
