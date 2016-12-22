<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magmodules\Mollie\Logger\MollieLogger;

class General extends AbstractHelper
{

    const MODULE_CODE = 'Magmodules_Mollie';
    const XML_PATH_MODULE_ACTIVE = 'payment/mollie_general/active';
    const XML_PATH_API_MODUS = 'payment/mollie_general/type';
    const XML_PATH_LIVE_APIKEY = 'payment/mollie_general/apikey_live';
    const XML_PATH_TEST_APIKEY = 'payment/mollie_general/apikey_test';
    const XML_PATH_DEBUG = 'payment/mollie_general/debug';
    const XML_PATH_STATUS_PROCESSING = 'payment/mollie_general/order_status_processing';
    const XML_PATH_STATUS_PENDING = 'payment/mollie_general/order_status_pending';
    const XML_PATH_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';

    /**
     * General constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     * @param MollieLogger $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        MollieLogger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->moduleList = $moduleList;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Availabiliy check, on Active & API Key
     *
     * @param $storeId
     * @return bool
     */
    public function isAvailable($storeId)
    {
        $active = $this->getStoreConfig(self::XML_PATH_MODULE_ACTIVE);
        if (!$active) {
            return false;
        }

        $apiKey = $this->getApiKey($storeId);
        if (!preg_match('/^(live|test)_\w+$/', $apiKey)) {
            $this->addTolog('error', 'Invalid Mollie API key.');
            return false;
        }
        
        return true;
    }

    /**
     * Returns API key
     *
     * @param $storeId
     * @return bool|mixed
     */
    public function getApiKey($storeId)
    {
        $modus = $this->getStoreConfig(self::XML_PATH_API_MODUS, $storeId);
        if ($modus == 'test') {
            return $this->getStoreConfig(self::XML_PATH_TEST_APIKEY, $storeId);
        } else {
            return $this->getStoreConfig(self::XML_PATH_LIVE_APIKEY, $storeId);
        }

        return false;
    }

    /**
     * Currecny check
     *
     * @param $currency
     * @return bool
     */
    public function isCurrencyAllowed($currency)
    {
        $allowed = ['EUR'];
        if (!in_array($currency, $allowed)) {
            return false;
        }
        
        return true;
    }

    /**
     * Method code for API
     *
     * @param $order
     * @return mixed
     */
    public function getMethodCode($order)
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();
        $methodCode = str_replace('mollie_methods_', '', $method);
        return $methodCode;
    }

    /**
     * Redirect Url Builder /w OrderId & UTM No Override
     *
     * @param $orderId
     * @return string
     */
    public function getRedirectUrl($orderId)
    {
        $urlParams = '?order_id=' . intval($orderId) . '&utm_nooverride=1';
        return $this->urlBuilder->getUrl('mollie/checkout/success/') . $urlParams;
    }

    /**
     * Webhook Url Builder
     *
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->urlBuilder->getUrl('mollie/checkout/webhook/');
    }

    /**
     * Selected processing status
     *
     * @param int $storeId
     * @return mixed
     */
    public function getStatusProcessing($storeId = 0)
    {
        return $this->getStoreConfig(self::XML_PATH_STATUS_PROCESSING, $storeId);
    }

    /**
     * Selected pending (payment) status
     *
     * @param int $storeId
     * @return mixed
     */
    public function getStatusPending($storeId = 0)
    {
        return $this->getStoreConfig(self::XML_PATH_STATUS_PENDING, $storeId);
    }

    /**
     * Send invoice
     *
     * @param int $storeId
     * @return mixed
     */
    public function sendInvoice($storeId = 0)
    {
        return (int)$this->getStoreConfig(self::XML_PATH_INVOICE_NOTIFY, $storeId);
    }

    /**
     * Get admin value by path and storeId
     *
     * @param $path
     * @param int $storeId
     * @return mixed
     */
    public function getStoreConfig($path, $storeId = 0)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Write to log
     *
     * @param $type
     * @param $data
     */
    public function addTolog($type, $data)
    {
        $debug = $this->getStoreConfig(self::XML_PATH_DEBUG);
        if ($debug) {
            if ($type == 'error') {
                $this->logger->addErrorLog($type, $data);
            } else {
                $this->logger->addInfoLog($type, $data);
            }
        }
    }

    /**
     * Returns current version of the extension for admin display
     *
     * @return mixed
     */
    public function getExtensionVersion()
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

        return $moduleInfo['setup_version'];
    }
}
