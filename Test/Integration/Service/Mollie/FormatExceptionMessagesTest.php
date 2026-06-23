<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Exception;
use Magento\Payment\Model\MethodInterface;
use Mollie\Payment\Service\Mollie\FormatExceptionMessages;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class FormatExceptionMessagesTest extends IntegrationTestCase
{
    public function testReturnsAllowedMessageWhenItMatches(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(
            new Exception('The billing country is not supported for this payment method.')
        );

        $this->assertSame('The billing country is not supported for this payment method.', $result);
    }

    public function testConvertsKnownMessageToExtendedVersion(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(
            new Exception('The webhook URL is invalid because it is unreachable from Mollie\'s point of view')
        );

        $this->assertStringContainsString('github.com/mollie/magento2/wiki', $result);
    }

    public function testReturnsTimeoutMessageForCurlError28WithMethodInstance(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $methodInstance = $this->createMock(MethodInterface::class);
        $methodInstance->method('getTitle')->willReturn('iDEAL');

        $result = $instance->execute(new Exception('cURL error 28: Operation timed out'), $methodInstance);

        $this->assertStringContainsString('Timeout', $result);
        $this->assertStringContainsString('iDEAL', $result);
    }

    public function testReturnsOriginalMessageForCurlError28WithoutMethodInstance(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(new Exception('cURL error 28: Operation timed out'));

        $this->assertSame('cURL error 28: Operation timed out', $result);
    }

    public function testReturnsOriginalMessageForUnknownException(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(new Exception('Something completely different went wrong'));

        $this->assertSame('Something completely different went wrong', $result);
    }

    public function testReturnsAllowedMessagePassedThroughConstructor(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class, [
            'allowedErrorMessages' => ['A custom allowed error message.'],
        ]);

        $result = $instance->execute(new Exception('A custom allowed error message.'));

        $this->assertSame('A custom allowed error message.', $result);
    }
}
