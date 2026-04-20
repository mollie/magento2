<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Compatibility;

use Laminas\Http\Headers;
use Laminas\Http\Response;
use Magento\Framework\HTTP\LaminasClient;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\WebhookUrlOptions;
use Mollie\Payment\Service\Mollie\SelfTests\TestWebhookEndpoint;
use PHPUnit\Framework\TestCase;

class TestWebhookEndpointTest extends TestCase
{
    private function createInstance(
        int $status,
        string $body,
        string $serverHeader,
        bool $isProductionMode = true,
        string $useWebhooks = WebhookUrlOptions::ENABLED,
    ): TestWebhookEndpoint {
        $config = $this->createMock(Config::class);
        $config->method('isProductionMode')->willReturn($isProductionMode);
        $config->method('useWebhooks')->willReturn($useWebhooks);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $response = new Response();
        $response->setStatusCode($status);
        $response->setContent($body);

        $headers = new Headers();
        if ($serverHeader !== '') {
            $headers->addHeaderLine('Server', $serverHeader);
        }
        $response->setHeaders($headers);

        $client = $this->createMock(LaminasClient::class);
        $client->method('send')->willReturn($response);

        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturn('https://example.com/mollie/checkout/webhook/');

        return new TestWebhookEndpoint($config, $storeManager, $client, $urlBuilder);
    }

    public function testSuccessfulWebhookEndpoint(): void
    {
        $instance = $this->createInstance(200, 'OK', 'nginx');
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('success', $messages[0]['type']);
    }

    public function testSuccessfulWebhookEndpointWithCloudflareShowsWarning(): void
    {
        $instance = $this->createInstance(200, 'OK', 'cloudflare');
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(2, $messages);
        $this->assertEquals('success', $messages[0]['type']);
        $this->assertEquals('warning', $messages[1]['type']);
        $this->assertStringContainsString('Cloudflare', $messages[1]['message']);
    }

    public function testCloudflareRedirectDetected(): void
    {
        $instance = $this->createInstance(302, '', 'cloudflare');
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('error', $messages[0]['type']);
        $this->assertStringContainsString('Cloudflare', $messages[0]['message']);
        $this->assertStringContainsString(
            'https://github.com/mollie/magento2/wiki/Cloudflare-Configuration-for-Mollie-Webhooks',
            $messages[0]['message']
        );
    }

    public function testCloudflare403Detected(): void
    {
        $instance = $this->createInstance(403, '', 'cloudflare');
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('error', $messages[0]['type']);
        $this->assertStringContainsString('Cloudflare', $messages[0]['message']);
    }

    public function testCloudflare503Detected(): void
    {
        $instance = $this->createInstance(503, '', 'cloudflare');
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('error', $messages[0]['type']);
        $this->assertStringContainsString('Cloudflare', $messages[0]['message']);
    }

    public function testNonCloudflareRedirectDetected(): void
    {
        $instance = $this->createInstance(302, '', 'nginx');
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('error', $messages[0]['type']);
        $this->assertStringContainsString('redirect', $messages[0]['message']);
        $this->assertStringNotContainsString('Cloudflare', $messages[0]['message']);
    }

    public function testUnexpectedResponseBody(): void
    {
        $instance = $this->createInstance(200, '<html>Challenge</html>', 'cloudflare');
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('error', $messages[0]['type']);
        $this->assertStringContainsString('Cloudflare', $messages[0]['message']);
    }

    public function testSkipsWhenWebhooksDisabledInTestMode(): void
    {
        $instance = $this->createInstance(
            200,
            'OK',
            '',
            isProductionMode: false,
            useWebhooks: WebhookUrlOptions::DISABLED,
        );
        $instance->execute();

        $this->assertCount(0, $instance->getMessages());
    }

    public function testGenericErrorResponse(): void
    {
        $instance = $this->createInstance(500, '', 'nginx');
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('error', $messages[0]['type']);
        $this->assertStringContainsString('500', $messages[0]['message']);
    }
}
