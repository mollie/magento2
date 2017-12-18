<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver;
use Mollie\Payment\Logger\MollieLogger;

class General extends AbstractHelper
{

    const MODULE_CODE = 'Mollie_Payment';
    const XML_PATH_MODULE_ACTIVE = 'payment/mollie_general/enabled';
    const XML_PATH_API_MODUS = 'payment/mollie_general/type';
    const XML_PATH_LIVE_APIKEY = 'payment/mollie_general/apikey_live';
    const XML_PATH_TEST_APIKEY = 'payment/mollie_general/apikey_test';
    const XML_PATH_DEBUG = 'payment/mollie_general/debug';
    const XML_PATH_LOADING_SCREEN = 'payment/mollie_general/loading_screen';
    const XML_PATH_STATUS_PROCESSING = 'payment/mollie_general/order_status_processing';
    const XML_PATH_STATUS_PENDING = 'payment/mollie_general/order_status_pending';
    const XML_PATH_STATUS_PENDING_BANKTRANSFER = 'payment/mollie_methods_banktransfer/order_status_pending';
    const XML_PATH_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';
    const XML_PATH_LOCALE = 'payment/mollie_general/locale';
    const XML_PATH_IMAGES = 'payment/mollie_general/payment_images';

    protected $metadata;
    protected $storeManager;
    protected $resourceConfig;
    protected $urlBuilder;
    protected $moduleList;
    protected $logger;
    protected $apiCheck;
    protected $apiKey;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * General constructor.
     *
     * @param Context                  $context
     * @param StoreManagerInterface    $storeManager
     * @param Config                   $resourceConfig
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $metadata
     * @param Resolver                 $resolver
     * @param MollieLogger             $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $resourceConfig,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        Resolver $resolver,
        MollieLogger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->moduleList = $moduleList;
        $this->metadata = $metadata;
        $this->resolver = $resolver;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Availabiliy check, on Active, API Client & API Key
     *
     * @param $storeId
     *
     * @return bool
     */
    public function isAvailable($storeId)
    {
        $active = $this->getStoreConfig(self::XML_PATH_MODULE_ACTIVE);
        if (!$active) {
            return false;
        }

        if (!$this->checkIfClassExists('Mollie_API_Client')) {
            return false;
        }

        $apiKey = $this->getApiKey($storeId);
        if (empty($apiKey)) {
            return false;
        }

        return true;
    }

    /**
     * Get admin value by path and storeId
     *
     * @param     $path
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStoreConfig($path, $storeId = 0)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Check is class extists, used for Mollie API check
     *
     * @param $class
     *
     * @return bool
     */
    public function checkIfClassExists($class)
    {
        if (!empty($this->apiCheck)) {
            return true;
        }

        if (class_exists($class)) {
            $this->apiCheck = true;
        }

        return $this->apiCheck;
    }

    /**
     * Returns API key
     *
     * @param $storeId
     *
     * @return bool|mixed
     */
    public function getApiKey($storeId = null)
    {
        if (!empty($this->apiKey)) {
            return $this->apiKey;
        }

        if (empty($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $modus = $this->getModus($storeId);

        if ($modus == 'test') {
            $apiKey = trim($this->getStoreConfig(self::XML_PATH_TEST_APIKEY, $storeId));
            if (empty($apiKey)) {
                $this->addTolog('error', 'Mollie API key not set (test modus)');
            }
            if (!preg_match('/^test_\w+$/', $apiKey)) {
                $this->addTolog('error', 'Mollie set to test modus, but API key does not start with "test_"');
            }
            $this->apiKey = $apiKey;
        } else {
            $apiKey = trim($this->getStoreConfig(self::XML_PATH_LIVE_APIKEY, $storeId));
            if (empty($apiKey)) {
                $this->addTolog('error', 'Mollie API key not set (live modus)');
            }
            if (!preg_match('/^live_\w+$/', $apiKey)) {
                $this->addTolog('error', 'Mollie set to live modus, but API key does not start with "live_"');
            }
            $this->apiKey = $apiKey;
        }

        return $this->apiKey;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getModus($storeId)
    {
        return $this->getStoreConfig(self::XML_PATH_API_MODUS, $storeId);
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
     * @param $storeId
     *
     * @return mixed
     */
    public function useLoadingScreen($storeId)
    {
        return $this->getStoreConfig(self::XML_PATH_LOADING_SCREEN, $storeId);
    }


    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function useImage($storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getStoreConfig(self::XML_PATH_IMAGES, $storeId);
    }

    /**
     * Disable extension function.
     * Used when Mollie API is not installed
     */
    public function disableExtension()
    {
        $this->resourceConfig->saveConfig(self::XML_PATH_MODULE_ACTIVE, 0, 'default', 0);
    }

    /**
     * Currency check
     *
     * @param $currency
     *
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
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStoreCurrencyCode($storeId = null)
    {
        return $this->storeManager->getStore($storeId)->getCurrentCurrency()->getCode();
    }

    /**
     * Method code for API
     *
     * @param $order
     *
     * @return mixed
     */
    public function getMethodCode($order)
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();
        $methodCode = str_replace('mollie_methods_', '', $method);

        // Mollie API uses mistercash instead of bancontact
        if ($methodCode == 'bancontact') {
            $methodCode = 'mistercash';
        }

        return $methodCode;
    }

    /**
     * Redirect Url Builder /w OrderId & UTM No Override
     *
     * @param $orderId
     *
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
     *
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
     *
     * @return mixed
     */
    public function getStatusPending($storeId = 0)
    {
        return $this->getStoreConfig(self::XML_PATH_STATUS_PENDING, $storeId);
    }

    /**
     * Selected pending (payment) status for banktransfer
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStatusPendingBanktransfer($storeId = 0)
    {
        return $this->getStoreConfig(self::XML_PATH_STATUS_PENDING_BANKTRANSFER, $storeId);
    }

    /**
     * Send invoice
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function sendInvoice($storeId = 0)
    {
        return (int)$this->getStoreConfig(self::XML_PATH_INVOICE_NOTIFY, $storeId);
    }

    /**
     * @return string
     */
    public function getLocaleCode()
    {
        $locale = $this->getStoreConfig(self::XML_PATH_LOCALE);

        if (!$locale) {
            return '';
        }

        if ($locale == 'store') {
            $localeCode = $this->resolver->getLocale();
            if (in_array($localeCode, $this->getSupportedLocal())) {
                return $localeCode;
            } else {
                return '';
            }
        }

        return $locale;
    }

    /**
     * List of supported local codes Mollie.
     *
     * @return array
     */
    public function getSupportedLocal()
    {
        return ['en_US', 'de_AT', 'de_CH', 'de_DE', 'es_ES', 'fr_BE', 'fr_FR', 'nl_BE', 'nl_NL'];
    }

    /**
     * Returns array of active methods with maximum order value
     *
     * @param $storeId
     *
     * @return array
     */
    public function getAllActiveMethods($storeId)
    {
        $activeMethods = [];
        $methodCodes = [
            'mollie_methods_bancontact',
            'mollie_methods_banktransfer',
            'mollie_methods_belfius',
            'mollie_methods_bitcoin',
            'mollie_methods_creditcard',
            'mollie_methods_ideal',
            'mollie_methods_kbc',
            'mollie_methods_paypal',
            'mollie_methods_paysafecard',
            'mollie_methods_sofort',
            'mollie_methods_giftcard'
        ];

        foreach ($methodCodes as $methodCode) {
            $activePath = 'payment/' . $methodCode . '/active';
            $active = $this->getStoreConfig($activePath, $storeId);

            if ($active) {
                $maxPath = 'payment/' . $methodCode . '/max_order_total';
                $max = $this->getStoreConfig($maxPath, $storeId);
                $code = str_replace('mollie_methods_', '', $methodCode);
                if ($code == 'bancontact') {
                    $code = 'mistercash';
                }
                $activeMethods[$methodCode] = ['code' => $code, 'max' => $max];
            }
        }

        return $activeMethods;
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

    /**
     * Returns current version of Magento
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->metadata->getVersion();
    }

    /**
     * @return string
     */
    public function getPhpApiErrorMessage()
    {
        return __(
            'Mollie API client for PHP is not installed, for more information about this issue see our %1 page.',
            '<a href="https://github.com/mollie/Magento2" target="_blank">GitHub</a>'
        );
    }
}
