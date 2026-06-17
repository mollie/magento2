<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Api\Http\Requests\CreatePaymentRequest;
use Mollie\Payment\Service\Mollie\BuildPaymentRequest;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class BuildPaymentRequestTest extends IntegrationTestCase
{
    public function testBuildsRequestWhenShippingAddressIsMissing(): void
    {
        /** @var BuildPaymentRequest $instance */
        $instance = $this->objectManager->create(BuildPaymentRequest::class);

        $result = $instance->execute([
            'description' => 'Order 0000025',
            'amount' => ['currency' => 'EUR', 'value' => '10.00'],
            'billingAddress' => ['email' => 'test@example.com'],
        ]);

        $this->assertInstanceOf(CreatePaymentRequest::class, $result);
    }

    public function testBuildsRequestWhenShippingAddressIsPresent(): void
    {
        /** @var BuildPaymentRequest $instance */
        $instance = $this->objectManager->create(BuildPaymentRequest::class);

        $result = $instance->execute([
            'description' => 'Order 0000025',
            'amount' => ['currency' => 'EUR', 'value' => '10.00'],
            'billingAddress' => ['email' => 'test@example.com'],
            'shippingAddress' => ['email' => 'test@example.com'],
        ]);

        $this->assertInstanceOf(CreatePaymentRequest::class, $result);
    }
}
