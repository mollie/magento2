<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Quote;

use Magento\Framework\App\Request\Http as HttpRequest;
use Mollie\Payment\Service\Quote\GetApplePayShippingMethodIdentifier;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GetApplePayShippingMethodIdentifierTest extends IntegrationTestCase
{
    public function testReturnsTheIdentifierOfTheSelectedShippingMethod(): void
    {
        $request = $this->requestWithShippingMethod(['identifier' => 'flatrate__SPLIT__flatrate']);

        $identifier = $this->objectManager->create(GetApplePayShippingMethodIdentifier::class)->execute($request);

        $this->assertSame('flatrate__SPLIT__flatrate', $identifier);
    }

    /**
     * @dataProvider unusableShippingMethodProvider
     */
    #[DataProvider('unusableShippingMethodProvider')]
    public function testReturnsNullWhenTheShippingMethodIsUnusable(mixed $shippingMethod): void
    {
        $request = $this->requestWithShippingMethod($shippingMethod);

        $identifier = $this->objectManager->create(GetApplePayShippingMethodIdentifier::class)->execute($request);

        $this->assertNull($identifier);
    }

    public static function unusableShippingMethodProvider(): array
    {
        return [
            'missing' => [null],
            'the string "undefined" sent by the Hyvä Apple Pay buttons' => ['undefined'],
            'any other plain string' => ['flatrate__SPLIT__flatrate'],
            'an array without an identifier' => [['label' => 'Flat Rate']],
            'an empty identifier' => [['identifier' => '']],
            'a nested array as identifier' => [['identifier' => ['flatrate']]],
        ];
    }

    private function requestWithShippingMethod(mixed $shippingMethod): HttpRequest
    {
        /** @var HttpRequest $request */
        $request = $this->objectManager->create(HttpRequest::class);
        $request->setParam('shippingMethod', $shippingMethod);

        return $request;
    }
}
