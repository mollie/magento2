<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;

class ApplePayValidation extends Action
{
    /**
     * @var Mollie
     */
    private $mollie;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Context $context,
        Mollie $mollie,
        StoreManagerInterface $storeManager,
        UrlInterface $url,
        Config $config
    ) {
        parent::__construct($context);

        $this->mollie = $mollie;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->config = $config;
    }

    public function execute()
    {
        $store = $this->storeManager->getStore();
        $api = $this->mollie->loadMollieApi($this->getLiveApiKey((int)$store->getId()));
        $url = $this->url->getBaseUrl();

        $result = $api->wallets->requestApplePayPaymentSession(
            parse_url($url, PHP_URL_HOST),
            $this->getRequest()->getParam('validationURL')
        );

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData(json_decode($result));

        return $response;
    }

    private function getLiveApiKey(int $storeId): string
    {
        $liveApikey = $this->config->getLiveApiKey($storeId);
        if (!$liveApikey) {
            throw new \Exception(__('For Apple Pay the live API key is required, even when in test mode'));
        }

        return $liveApikey;
    }
}
