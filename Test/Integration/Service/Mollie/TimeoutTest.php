<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Exception;
use Mollie\Payment\Service\Mollie\Timeout;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TimeoutTest extends IntegrationTestCase
{
    public function testReturnsResultWhenCallbackSucceeds(): void
    {
        /** @var Timeout $instance */
        $instance = $this->objectManager->create(Timeout::class);

        $result = $instance->retry(fn () => 'success');

        $this->assertSame('success', $result);
    }

    public function testRetriesOnTimeoutAndReturnsResultOnLaterAttempt(): void
    {
        /** @var Timeout $instance */
        $instance = $this->objectManager->create(Timeout::class);

        $attempts = 0;
        $result = $instance->retry(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new Exception('cURL error 28: Operation timed out');
            }

            return 'success';
        });

        $this->assertSame('success', $result);
        $this->assertSame(3, $attempts);
    }

    public function testThrowsAfterThreeTimeouts(): void
    {
        /** @var Timeout $instance */
        $instance = $this->objectManager->create(Timeout::class);

        $attempts = 0;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('cURL error 28');

        try {
            $instance->retry(function () use (&$attempts) {
                $attempts++;
                throw new Exception('cURL error 28: Operation timed out');
            });
        } finally {
            $this->assertSame(3, $attempts);
        }
    }

    public function testRethrowsNonTimeoutExceptionImmediately(): void
    {
        /** @var Timeout $instance */
        $instance = $this->objectManager->create(Timeout::class);

        $attempts = 0;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Some other error');

        try {
            $instance->retry(function () use (&$attempts) {
                $attempts++;
                throw new Exception('Some other error');
            });
        } finally {
            $this->assertSame(1, $attempts);
        }
    }
}
