<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Framework\Exception\LocalizedException;
use Mollie\Api\Fake\MockResponse;
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
