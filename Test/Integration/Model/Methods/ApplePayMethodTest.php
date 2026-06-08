<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\PendingRequest;
use Mollie\Api\Http\Requests\GetEnabledMethodsRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;

class ApplePayMethodTest extends AbstractTestMethod
{
    protected ?string $instance = ApplePay::class;

    protected ?string $code = 'applepay';

    public function testTheIncludeWalletsParameterIsUsed(): void
    {
        $client = MollieApiClient::fake([
            GetEnabledMethodsRequest::class => function (PendingRequest $request): MockResponse {
                $this->assertTrue($request->getRequest()->query()->has('includeWallets'));
                $this->assertEquals('applepay', $request->getRequest()->query()->get('includeWallets'));

                return MockResponse::ok('method-list');
            },
        ]);

        $mollieHelperMock = $this->createMock(General::class);
        $mollieHelperMock->method('getOrderAmountByQuote')->willReturn(['value' => 100, 'currency' => 'EUR']);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, [
            'mollieHelper' => $mollieHelperMock,
        ]);
        $instance->getActiveMethods();
    }
}
