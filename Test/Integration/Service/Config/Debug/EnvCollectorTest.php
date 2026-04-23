<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Config\Debug;

use Magento\Framework\App\DeploymentConfig\Reader;
use Mollie\Payment\Service\Config\Debug\EnvCollector;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class EnvCollectorTest extends IntegrationTestCase
{
    private function createCollector(array $envData): EnvCollector
    {
        $reader = $this->createMock(Reader::class);
        $reader->method('load')->willReturn($envData);

        return $this->objectManager->create(EnvCollector::class, ['reader' => $reader]);
    }

    private function collect(array $envData): string
    {
        return $this->createCollector($envData)->collect()['env.redacted.php.txt'];
    }

    public function testPasswordIsRedacted(): void
    {
        $result = $this->collect(['db' => ['connection' => ['default' => ['password' => 'supersecret']]]]);

        $this->assertStringNotContainsString('supersecret', $result);
        $this->assertStringContainsString(EnvCollector::REDACTED, $result);
    }

    public function testApiKeyIsRedacted(): void
    {
        $result = $this->collect(['some_service' => ['api_key' => 'live_abc123']]);

        $this->assertStringNotContainsString('live_abc123', $result);
        $this->assertStringContainsString(EnvCollector::REDACTED, $result);
    }

    public function testSaltIsRedacted(): void
    {
        $result = $this->collect(['graphql' => ['id_salt' => 'bgkoNvEwustlPeW6pBCMkME56fdXESDq']]);

        $this->assertStringNotContainsString('bgkoNvEwustlPeW6pBCMkME56fdXESDq', $result);
        $this->assertStringContainsString(EnvCollector::REDACTED, $result);
    }

    public function testSecretIsRedacted(): void
    {
        $result = $this->collect(['cache' => ['frontend' => ['secret_key' => 'abc123']]]);

        $this->assertStringNotContainsString('abc123', $result);
        $this->assertStringContainsString(EnvCollector::REDACTED, $result);
    }

    public function testDbConnectionUsernameIsRedacted(): void
    {
        $result = $this->collect(['db' => ['connection' => ['default' => ['username' => 'magento_user']]]]);

        $this->assertStringNotContainsString('magento_user', $result);
        $this->assertStringContainsString(EnvCollector::REDACTED, $result);
    }

    public function testNonSensitiveValuesAreNotRedacted(): void
    {
        $result = $this->collect(['db' => ['connection' => ['default' => ['host' => 'localhost', 'dbname' => 'magento']]]]);

        $this->assertStringContainsString('localhost', $result);
        $this->assertStringContainsString('magento', $result);
        $this->assertStringNotContainsString(EnvCollector::REDACTED, $result);
    }

    public function testStructureIsPreservedAroundRedactedValues(): void
    {
        $result = $this->collect([
            'db' => [
                'connection' => [
                    'default' => [
                        'host' => 'localhost',
                        'password' => 'supersecret',
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('localhost', $result);
        $this->assertStringNotContainsString('supersecret', $result);
    }

    public function testDeeplyNestedPasswordIsRedacted(): void
    {
        $result = $this->collect(['a' => ['b' => ['c' => ['d' => ['password' => 'deep_secret']]]]]);

        $this->assertStringNotContainsString('deep_secret', $result);
        $this->assertStringContainsString(EnvCollector::REDACTED, $result);
    }

    public function testEmptyEnvReturnsEmptyMessage(): void
    {
        $result = $this->collect([]);

        $this->assertStringContainsString('empty or missing', $result);
    }
}
