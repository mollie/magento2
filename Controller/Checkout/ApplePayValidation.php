<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Exception;
use Laminas\Uri\Http;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;

class ApplePayValidation extends Action implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private Mollie $mollie,
        private StoreManagerInterface $storeManager,
        private UrlInterface $url,
        private Config $config,
        private Http $http,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $validationUrl = $this->getRequest()->getParam('validationURL');
            $this->assertValidApplePayUrl($validationUrl);

            $store = $this->storeManager->getStore();
            $api = $this->mollie->loadMollieApi($this->getLiveApiKey((int) $store->getId()));

            $result = $api->wallets->requestApplePayPaymentSession(
                $this->http->parse($this->url->getBaseUrl())->getHost(),
                $validationUrl,
            );

            $response->setData($result->toArray());
        } catch (Exception $e) {
            $response->setHttpResponseCode(400);
            $response->setData(['error' => true, 'message' => $e->getMessage()]);
        }

        return $response;
    }

    private function assertValidApplePayUrl(?string $url): void
    {
        if (!$url || !preg_match('#^https://apple-pay-gateway[a-z0-9.-]*\.apple\.com/#', $url)) {
            throw new LocalizedException(__('Invalid Apple Pay validation URL'));
        }
    }

    private function getLiveApiKey(int $storeId): string
    {
        $liveApikey = $this->config->getLiveApiKey($storeId);
        if (!$liveApikey) {
            throw new LocalizedException(__('For Apple Pay the live API key is required, even when in test mode'));
        }

        return $liveApikey;
    }
}
