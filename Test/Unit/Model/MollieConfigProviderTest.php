<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Unit\Model;

use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Unit\UnitTestCase;
use Magento\Quote\Model\Quote;

class MollieConfigProviderTest extends UnitTestCase
{
    public function testCallsTheApiOnlyOnce()
    {
        $client = new \Mollie\Api\MollieApiClient;

        $mollieHelperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $mollieHelperMock->method('getOrderAmountByQuote')->willReturn(['value' => 100, 'currency' => 'EUR']);

        $methodsEndpointMock = $this->createMock(\Mollie\Api\Endpoints\MethodEndpoint::class);
        $methodsEndpointMock->expects($this->once())->method('allActive')->willReturn([
            (object)[
                'id' => 'ideal',
                'image' => (object)[
                    'size2x' => 'ideal.svg',
                ]
            ]
        ]);
        $client->methods = $methodsEndpointMock;

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->getObject(MollieConfigProvider::class, [
            'mollieHelper' => $mollieHelperMock,
        ]);

        $cart = $this->createMock(Quote::class);
        $cart->method('getBillingAddress')->willReturnSelf();

        $result = $instance->getActiveMethods($client, $cart);
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('mollie_methods_ideal', $result);
        $this->assertEquals('ideal.svg', $result['mollie_methods_ideal']['image']);

        $result = $instance->getActiveMethods($client);
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('mollie_methods_ideal', $result);
        $this->assertEquals('ideal.svg', $result['mollie_methods_ideal']['image']);
    }
}
