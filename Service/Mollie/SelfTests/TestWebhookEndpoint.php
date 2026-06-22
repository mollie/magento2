<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Exception;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\WebhookUrlOptions;

class TestWebhookEndpoint extends AbstractSelfTest
{
    private ?Response $response = null;

    public function __construct(
        private Config $config,
        private StoreManagerInterface $storeManager,
        private Client $client,
        private UrlInterface $urlBuilder,
    ) {}

    public function execute(): void
    {
        if (!$this->config->isProductionMode() &&
            $this->config->useWebhooks() == WebhookUrlOptions::DISABLED) {
            return;
        }

        $webhookUrl = $this->getWebhookUrl();

        if (!$this->performWebhookRequest($webhookUrl)) {
            return;
        }

        $status = $this->response->getStatusCode();
        $isCloudflare = $this->isCloudflareResponse();

        if ($status === 200) {
            $this->handleSuccessResponse($webhookUrl, $isCloudflare);
            return;
        }

        if ($status >= 300 && $status < 400) {
            $this->handleRedirectResponse($webhookUrl, $status, $isCloudflare);
            return;
        }

        if ($status === 403 || $status === 503) {
            $this->handleBlockedResponse($webhookUrl, $status, $isCloudflare);
            return;
        }

        $this->addMessage('error', __(
            'Error: The webhook endpoint returned HTTP %1. '
            . 'Please verify that the webhook URL is accessible: %2',
            $status,
            $webhookUrl
        ));
    }

    private function performWebhookRequest(string $webhookUrl): bool
    {
        try {
            $this->client->setOptions(['maxredirects' => 0]);
            $this->client->setUri($webhookUrl);
            $this->client->setMethod(Request::METHOD_POST);
            $this->client->setParameterPost(['testByMollie' => 1]);
            $this->response = $this->client->send();
        } catch (Exception $exception) {
            $this->addMessage('error', __(
                'Error: Unable to reach the webhook endpoint: %1. Error: %2',
                $webhookUrl,
                $exception->getMessage()
            ));
            return false;
        }

        return true;
    }

    private function isCloudflareResponse(): bool
    {
        $headers = $this->response->getHeaders();
        if (!$headers->has('Server')) {
            return false;
        }

        $serverHeader = $headers->get('Server');
        if ($serverHeader === false) {
            return false;
        }

        if ($serverHeader instanceof \Traversable) {
            foreach ($serverHeader as $header) {
                if (stripos($header->getFieldValue(), 'cloudflare') !== false) {
                    return true;
                }
            }
            return false;
        }

        return stripos($serverHeader->getFieldValue(), 'cloudflare') !== false;
    }

    private function handleSuccessResponse(string $webhookUrl, bool $isCloudflare): void
    {
        $body = trim($this->response->getBody());

        if ($body === 'OK') {
            $this->addMessage('success', __('Success: The webhook endpoint is reachable.'));

            if ($isCloudflare) {
                $this->addCloudflareDetectedWarning();
            }

            return;
        }

        if ($isCloudflare) {
            $this->addCloudflareBlockingWarning($webhookUrl);
            return;
        }

        $this->addMessage('error', __(
            'Error: The webhook endpoint returned an unexpected response (HTTP 200). '
            . 'Please verify that the webhook URL is correct: %1',
            $webhookUrl
        ));
    }

    private function handleRedirectResponse(string $webhookUrl, int $status, bool $isCloudflare): void
    {
        if ($isCloudflare) {
            $this->addCloudflareBlockingWarning($webhookUrl);
            return;
        }

        $this->addMessage('error', __(
            'Error: The webhook endpoint returned a redirect (HTTP %1). '
            . 'This may cause webhook processing to fail. URL: %2',
            $status,
            $webhookUrl
        ));
    }

    private function handleBlockedResponse(string $webhookUrl, int $status, bool $isCloudflare): void
    {
        if ($isCloudflare) {
            $this->addCloudflareBlockingWarning($webhookUrl);
            return;
        }

        $this->addMessage('error', __(
            'Error: The webhook endpoint returned HTTP %1. '
            . 'Please verify that the webhook URL is accessible: %2',
            $status,
            $webhookUrl
        ));
    }

    private function addCloudflareDetectedWarning(): void
    {
        $wikiUrl = 'https://github.com/mollie/magento2/wiki/Cloudflare-Configuration-for-Mollie-Webhooks';

        $this->addMessage('warning', __(
            'Warning: We detected that Cloudflare is being used. '
            . 'Please read <a href="%1" target="_blank">this wiki</a> to be sure that everything works as expected.',
            $wikiUrl
        ));
    }

    private function addCloudflareBlockingWarning(string $webhookUrl): void
    {
        $wikiUrl = 'https://github.com/mollie/magento2/wiki/Cloudflare-Configuration-for-Mollie-Webhooks';

        $this->addMessage('error', __(
            'Error: Cloudflare appears to be blocking or redirecting the webhook endpoint (%1). '
            . 'Please configure Cloudflare to allow POST requests to the Mollie webhook URL. '
            . 'See <a href="%2" target="_blank">this wiki</a> for instructions.',
            $webhookUrl,
            $wikiUrl
        ));
    }

    private function getWebhookUrl(): string
    {
        $storeId = (int)$this->storeManager->getStore()->getId();

        if ($this->config->useWebhooks($storeId) == WebhookUrlOptions::CUSTOM_URL) {
            return $this->config->customWebhookUrl($storeId);
        }

        $this->urlBuilder->setScope($storeId);

        return $this->urlBuilder->getUrl('mollie/checkout/webhook/');
    }
}
