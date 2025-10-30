<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Laminas\Uri\Http;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;

class ApplePayValidation extends Action
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

    public function execute()
    {
        $store = $this->storeManager->getStore();
        $api = $this->mollie->loadMollieApi($this->getLiveApiKey((int) $store->getId()));
        $url = $this->url->getBaseUrl();

        $result = $api->wallets->requestApplePayPaymentSession(
            $this->http->parse($url)->getHost(),
            $this->getRequest()->getParam('validationURL'),
        );

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($result->toArray());

        return $response;
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
