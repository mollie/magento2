<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Mollie;

class ApplePayValidation extends Action
{
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

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

    public function __construct(
        Context $context,
        MollieHelper $mollieHelper,
        Mollie $mollie,
        StoreManagerInterface $storeManager,
        UrlInterface $url
    ) {
        parent::__construct($context);

        $this->mollieHelper = $mollieHelper;
        $this->mollie = $mollie;
        $this->storeManager = $storeManager;
        $this->url = $url;
    }

    public function execute()
    {
        $store = $this->storeManager->getStore();
        $api = $this->mollie->loadMollieApi($this->mollieHelper->getApiKey($store->getId()));
        $url = $this->url->getBaseUrl();

        $result = $api->wallets->requestApplePayPaymentSession(
            parse_url($url, PHP_URL_HOST),
            $this->getRequest()->getParam('validationURL')
        );

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData(json_decode($result));

        return $response;
    }
}
