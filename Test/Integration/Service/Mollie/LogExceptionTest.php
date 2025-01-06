<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Service\Mollie\LogException;
use Mollie\Payment\Test\Fakes\ConfigFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LogExceptionTest extends IntegrationTestCase
{
    public function testLogsMessages(): void
    {
        $exception = new ApiException('This is a test exception triggered in ' . __FILE__);

        $configFake = $this->objectManager->create(ConfigFake::class);

        /** @var LogException $instance */
        $instance = $this->objectManager->create(LogException::class, [
            'config' => $configFake,
        ]);
        $instance->execute($exception);

        $this->assertCount(1, $configFake->getLoggedMessages());
    }

    public function testSkipsSomeMessages(): void
    {
        $exception = new ApiException('The \'billingAddress.familyName\' field contains characters that are not allowed');

        $configFake = $this->objectManager->create(ConfigFake::class);

        /** @var LogException $instance */
        $instance = $this->objectManager->create(LogException::class, [
            'config' => $configFake,
        ]);
        $instance->execute($exception);

        $this->assertCount(0, $configFake->getLoggedMessages());
    }

    public function testCanAddOwnExceptions(): void
    {
        $exception = new ApiException('Some random message');

        $configFake = $this->objectManager->create(ConfigFake::class);

        /** @var LogException $instance */
        $instance = $this->objectManager->create(LogException::class, [
            // Normally you would do this through di.xml, but when testing we can do it like this.
            'messagesToSkip' => [
                'Some random message',
            ],
            'config' => $configFake,
        ]);
        $instance->execute($exception);

        $this->assertCount(0, $configFake->getLoggedMessages());
    }
}
