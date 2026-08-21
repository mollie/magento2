<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Helper;

use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\PendingRequest;
use Mollie\Api\Http\Requests\GetEnabledMethodsRequest;
use Mollie\Api\Http\Requests\GetPaginatedTerminalsRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Helper\Tests;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TestsTest extends IntegrationTestCase
{
    public function testRequestsTheWalletMethodsForBothApiKeys(): void
    {
        $requestedWallets = [];

        $client = MollieApiClient::fake([
            GetEnabledMethodsRequest::class => function (PendingRequest $request) use (&$requestedWallets): MockResponse {
                $requestedWallets[] = $request->getRequest()->query()->get('includeWallets');

                return MockResponse::ok('method-list');
            },
            GetPaginatedTerminalsRequest::class => MockResponse::ok('terminal-list'),
        ], true);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        /** @var Tests $instance */
        $instance = $this->objectManager->create(Tests::class);
        $instance->getMethods('test_dummyapikeythatisvalidandislongenough', 'live_dummyapikeythatisvalidandislongenough');

        $this->assertEquals(['applepay,googlepay', 'applepay,googlepay'], $requestedWallets);
    }
}
