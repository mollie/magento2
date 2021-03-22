<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Service\Mollie\Parameters\ParameterPartInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MethodParametersTest extends IntegrationTestCase
{
    const DEFAULT_INPUT = [
        'amount[value]' => 10,
        'amount[currency]' => 'EUR',
        'resource' => 'orders',
        'includeWallets' => 'applepay',
    ];

    public function testChangesNothingWhenNoPartsAreConfigured()
    {
        /** @var MethodParameters $instance */
        $instance = $this->objectManager->create(MethodParameters::class, ['parametersParts' => []]);

        $result = $instance->enhance(static::DEFAULT_INPUT, $this->objectManager->create(CartInterface::class));

        $this->assertEquals(static::DEFAULT_INPUT, $result);
    }

    public function testTheParametersCanBeAdjusted()
    {
        $part = new class() implements ParameterPartInterface {
            public function enhance(array $parameters, CartInterface $cart): array
            {
                return $parameters + ['test' => true];
            }
        };

        /** @var MethodParameters $instance */
        $instance = $this->objectManager->create(MethodParameters::class, ['parametersParts' => [
            'testPart' => $part,
        ]]);

        $result = $instance->enhance(static::DEFAULT_INPUT, $this->objectManager->create(CartInterface::class));

        $this->assertEquals(static::DEFAULT_INPUT + ['test' => true], $result);
    }

    public function testSupportsMultipleParameterParts()
    {
        $part1 = new class() implements ParameterPartInterface {
            public function enhance(array $parameters, CartInterface $cart): array
            {
                return $parameters + ['test1' => true];
            }
        };

        $part2 = new class() implements ParameterPartInterface {
            public function enhance(array $parameters, CartInterface $cart): array
            {
                return $parameters + ['test2' => true];
            }
        };

        /** @var MethodParameters $instance */
        $instance = $this->objectManager->create(MethodParameters::class, [
            'parametersParts' => [
                'test1' => $part1,
                'test2' => $part2,
            ]
        ]);

        $result = $instance->enhance(static::DEFAULT_INPUT, $this->objectManager->create(CartInterface::class));

        $this->assertEquals(static::DEFAULT_INPUT + ['test1' => true, 'test2' => true], $result);
    }
}
