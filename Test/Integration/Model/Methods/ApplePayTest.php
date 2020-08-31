<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Integration\Model\Methods\AbstractMethodTest;

class ApplePayTest extends AbstractMethodTest
{
    protected $instance = ApplePay::class;

    protected $code = 'applepay';

    public function testTheIncludeWalletsParameterIsUsed()
    {
        $mollieApiClient = $this->createMock(MollieApiClient::class);
        $mollieApiClient->methods = $this->createMock(MethodEndpoint::class);

        $mollieApiClient->methods->expects($this->once())->method('all')->with($this->callback(function ($arguments) {
            $this->assertArrayHasKey('includeWallets', $arguments);
            $this->assertEquals('applepay', $arguments['includeWallets']);

            return true;
        }));

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->get(MollieConfigProvider::class);
        $instance->getActiveMethods($mollieApiClient);
    }
}
