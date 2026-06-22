<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GetMollieStatusResultTest extends IntegrationTestCase
{
    public function testSetTheMethodCorrect(): void
    {
        $instance = $this->objectManager->create(GetMollieStatusResult::class, [
            'status' => 'open',
            'method' => 'mollie_methods_banktransfer',
        ]);

        $this->assertEquals('banktransfer', $instance->getMethod());
    }

    /**
     * @dataProvider returnsTheCorrectStatusForAsyncMethodProvider
     * @return void
     */
    #[DataProvider('returnsTheCorrectStatusForAsyncMethodProvider')]
    public function testReturnsTheCorrectStatusForAsyncMethod(string $status, string $method): void
    {
        $instance = $this->objectManager->create(GetMollieStatusResult::class, [
            'status' => $status,
            'method' => $method,
        ]);

        $this->assertTrue($instance->shouldRedirectToSuccessPage());
    }

    public static function returnsTheCorrectStatusForAsyncMethodProvider(): array
    {
        return [
            'open, banktransfer' => ['open', 'banktransfer'],
            'open, mollie_methods_banktransfer' => ['open', 'mollie_methods_banktransfer'],
            'open, paybybank' => ['open', 'paybybank'],
            'open, mollie_methods_paybybank' => ['open', 'mollie_methods_paybybank'],
        ];
    }

    /**
     * @dataProvider returnsToSuccessWhenNotBanktransferButHasTheCorrectStatusProvider
     * @return void
     */
    #[DataProvider('returnsToSuccessWhenNotBanktransferButHasTheCorrectStatusProvider')]
    public function testReturnsToSuccessWhenNotBanktransferButHasTheCorrectStatus(string $status, string $method): void
    {
        $instance = $this->objectManager->create(GetMollieStatusResult::class, [
            'status' => $status,
            'method' => $method,
        ]);

        $this->assertTrue($instance->shouldRedirectToSuccessPage());
    }

    public static function returnsToSuccessWhenNotBanktransferButHasTheCorrectStatusProvider(): array
    {
        return [
            'paid, ideal' => ['paid', 'ideal'],
            'paid, mollie_methods_ideal' => ['paid', 'mollie_methods_ideal'],
            'pending, ideal' => ['pending', 'ideal'],
            'authorized, klarna' => ['authorized', 'klarna'],
            'authorized, mollie_methods_klarna' => ['authorized', 'mollie_methods_klarna'],
        ];
    }

    /**
     * @dataProvider isAwaitingConfirmationProvider
     */
    #[DataProvider('isAwaitingConfirmationProvider')]
    public function testIsAwaitingConfirmation(string $status, string $method, bool $expected): void
    {
        $instance = $this->objectManager->create(GetMollieStatusResult::class, [
            'status' => $status,
            'method' => $method,
        ]);

        $this->assertEquals($expected, $instance->isAwaitingConfirmation());
    }

    public static function isAwaitingConfirmationProvider(): array
    {
        return [
            'open, paypal' => ['open', 'paypal', true],
            'open, mollie_methods_paypal' => ['open', 'mollie_methods_paypal', true],
            'open, banktransfer — not awaiting, goes to success directly' => ['open', 'banktransfer', false],
            'open, mollie_methods_banktransfer' => ['open', 'mollie_methods_banktransfer', false],
            'open, paybybank — async, goes to success directly' => ['open', 'paybybank', false],
            'open, mollie_methods_paybybank' => ['open', 'mollie_methods_paybybank', false],
            'paid, ideal — confirmed, not awaiting' => ['paid', 'ideal', false],
            'canceled, paypal' => ['canceled', 'paypal', false],
        ];
    }
}
