<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Tests\Unit\Model\Methods\AbstractMethodTest;

class ApplePayTest extends AbstractMethodTest
{
    protected $instance = ApplePay::class;

    protected $code = 'mollie_methods_applepay';

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
        $instance = $this->objectManager->getObject(MollieConfigProvider::class);
        $instance->getActiveMethods($mollieApiClient);
    }
}
