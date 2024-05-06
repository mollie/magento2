<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GetMollieStatusResultTest extends IntegrationTestCase
{
    public function testSetTheMethodCorrect(): void
    {
        $instance = $this->objectManager->create(GetMollieStatusResult::class, [
            'status' => 'created',
            'method' => 'mollie_methods_banktransfer',
        ]);

        $this->assertEquals('banktransfer', $instance->getMethod());
    }

    /**
     * @dataProvider returnsTheCorrectStatusForBanktransferProvider
     * @return void
     */
    public function testReturnsTheCorrectStatusForBanktransfer(string $status, string $method): void
    {
        $instance = $this->objectManager->create(GetMollieStatusResult::class, [
            'status' => $status,
            'method' => $method,
        ]);

        $this->assertTrue($instance->shouldRedirectToSuccessPage());
    }

    public function returnsTheCorrectStatusForBanktransferProvider(): array
    {
        return [
            'created, banktransfer' => ['created', 'banktransfer'],
            'created, mollie_methods_banktransfer' => ['created', 'mollie_methods_banktransfer'],
            'open, banktransfer' => ['open', 'banktransfer'],
            'open, mollie_methods_banktransfer' => ['open', 'mollie_methods_banktransfer'],
        ];
    }

    /**
     * @dataProvider returnsToSuccessWhenNotBanktransferButHasTheCorrectStatusProvider
     * @return void
     */
    public function testReturnsToSuccessWhenNotBanktransferButHasTheCorrectStatus(string $status, string $method): void
    {
        $instance = $this->objectManager->create(GetMollieStatusResult::class, [
            'status' => $status,
            'method' => $method,
        ]);

        $this->assertTrue($instance->shouldRedirectToSuccessPage());
    }

    public function returnsToSuccessWhenNotBanktransferButHasTheCorrectStatusProvider(): array
    {
        return [
            'paid, ideal' => ['paid', 'ideal'],
            'paid, mollie_methods_ideal' => ['paid', 'mollie_methods_ideal'],
            'pending, ideal' => ['pending', 'ideal'],
            'authorized, klarnapaylater' => ['authorized', 'klarnapaylater'],
            'authorized, mollie_methods_klarnapaylater' => ['authorized', 'mollie_methods_klarnapaylater'],
        ];
    }
}
