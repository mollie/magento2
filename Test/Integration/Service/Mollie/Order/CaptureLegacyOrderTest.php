<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Framework\Exception\LocalizedException;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\PendingRequest;
use Mollie\Api\Http\Requests\DynamicPostRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\CaptureLegacyOrder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CaptureLegacyOrderTest extends IntegrationTestCase
{
    public function testShipsTheLegacyOrderAndReturnsTheShipmentId(): void
    {
        $client = MollieApiClient::fake([
            DynamicPostRequest::class => MockResponse::created(
                '{"resource":"shipment","id":"shp_abc123","orderId":"ord_legacy1"}',
            ),
        ]);

        $instance = $this->objectManager->create(CaptureLegacyOrder::class);
        $result = $instance->execute($client, 'ord_legacy1');

        $this->assertEquals('shp_abc123', $result);
    }

    public function testShipsTheWholeOrderWhenNoLinesAreProvided(): void
    {
        $client = MollieApiClient::fake([
            DynamicPostRequest::class => MockResponse::created(
                '{"resource":"shipment","id":"shp_abc123","orderId":"ord_legacy1"}',
            ),
        ], true);

        $instance = $this->objectManager->create(CaptureLegacyOrder::class);
        $instance->execute($client, 'ord_legacy1');

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertArrayNotHasKey('lines', $this->payloadOf($request));

            return true;
        });
    }

    public function testShipsOnlyTheProvidedLinesForAPartialCapture(): void
    {
        $client = MollieApiClient::fake([
            DynamicPostRequest::class => MockResponse::created(
                '{"resource":"shipment","id":"shp_abc123","orderId":"ord_legacy1"}',
            ),
        ], true);

        $lines = [['id' => 'odl_1', 'quantity' => 1]];

        $instance = $this->objectManager->create(CaptureLegacyOrder::class);
        $result = $instance->execute($client, 'ord_legacy1', ['lines' => $lines]);

        $this->assertEquals('shp_abc123', $result);

        $client->assertSent(function (PendingRequest $request) use ($lines): bool {
            $this->assertSame($lines, $this->payloadOf($request)['lines'] ?? null);

            return true;
        });
    }

    private function payloadOf(PendingRequest $request): array
    {
        $payload = $request->payload();
        if ($payload !== null) {
            return $payload->all();
        }

        return json_decode((string)$request->createPsrRequest()->getBody(), true) ?? [];
    }

    public function testThrowsWhenTheShipmentResponseHasNoId(): void
    {
        $client = MollieApiClient::fake([
            DynamicPostRequest::class => MockResponse::created('{"resource":"shipment","orderId":"ord_legacy1"}'),
        ]);

        $instance = $this->objectManager->create(CaptureLegacyOrder::class);

        $this->expectException(LocalizedException::class);
        $instance->execute($client, 'ord_legacy1');
    }
}
