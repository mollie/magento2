<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\DynamicGetRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\ResolvePaymentId;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ResolvePaymentIdTest extends IntegrationTestCase
{
    public function testReturnsPaymentIdUnchangedWhenItIsNotALegacyOrderId(): void
    {
        $client = MollieApiClient::fake([]);

        $instance = $this->objectManager->create(ResolvePaymentId::class);
        $result = $instance->execute($client, 'tr_abc123');

        $this->assertEquals('tr_abc123', $result);
    }

    public function testResolvesLegacyOrderIdToItsSettledPayment(): void
    {
        $client = MollieApiClient::fake([
            DynamicGetRequest::class => MockResponse::ok($this->orderWithPaymentsResponse()),
        ]);

        $instance = $this->objectManager->create(ResolvePaymentId::class);
        $result = $instance->execute($client, 'ord_legacy1');

        $this->assertEquals('tr_paid456', $result);
    }

    public function testFallsBackToTheLastPaymentWhenNoneAreSettled(): void
    {
        $client = MollieApiClient::fake([
            DynamicGetRequest::class => MockResponse::ok($this->orderWithoutSettledPaymentsResponse()),
        ]);

        $instance = $this->objectManager->create(ResolvePaymentId::class);
        $result = $instance->execute($client, 'ord_legacy2');

        $this->assertEquals('tr_canceled789', $result);
    }

    public function testThrowsWhenLegacyOrderHasNoPayments(): void
    {
        $client = MollieApiClient::fake([
            DynamicGetRequest::class => MockResponse::ok('{"resource":"order","id":"ord_empty","_embedded":{"payments":[]}}'),
        ]);

        $instance = $this->objectManager->create(ResolvePaymentId::class);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $instance->execute($client, 'ord_empty');
    }

    private function orderWithPaymentsResponse(): string
    {
        return '{
            "resource": "order",
            "id": "ord_legacy1",
            "_embedded": {
                "payments": [
                    {"resource": "payment", "id": "tr_failed123", "status": "failed"},
                    {"resource": "payment", "id": "tr_paid456", "status": "paid"}
                ]
            }
        }';
    }

    private function orderWithoutSettledPaymentsResponse(): string
    {
        return '{
            "resource": "order",
            "id": "ord_legacy2",
            "_embedded": {
                "payments": [
                    {"resource": "payment", "id": "tr_expired456", "status": "expired"},
                    {"resource": "payment", "id": "tr_canceled789", "status": "canceled"}
                ]
            }
        }';
    }
}
