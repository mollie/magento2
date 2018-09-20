<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver;
use Mollie\Payment\Logger\MollieLogger;

/**
 * Class General
 *
 * @package Mollie\Payment\Helper
 */
class General extends AbstractHelper
{

    const MODULE_CODE = 'Mollie_Payment';
    const SUPPORTED_LOCAL = ['en_US', 'de_AT', 'de_CH', 'de_DE', 'es_ES', 'fr_BE', 'fr_FR', 'nl_BE', 'nl_NL'];
    const CURRENCIES_WITHOUT_DECIMAL = ['JPY'];

    const XML_PATH_MODULE_ACTIVE = 'payment/mollie_general/enabled';
    const XML_PATH_API_MODUS = 'payment/mollie_general/type';
    const XML_PATH_LIVE_APIKEY = 'payment/mollie_general/apikey_live';
    const XML_PATH_TEST_APIKEY = 'payment/mollie_general/apikey_test';
    const XML_PATH_DEBUG = 'payment/mollie_general/debug';
    const XML_PATH_LOADING_SCREEN = 'payment/mollie_general/loading_screen';
    const XML_PATH_STATUS_PROCESSING = 'payment/mollie_general/order_status_processing';
    const XML_PATH_STATUS_PENDING = 'payment/mollie_general/order_status_pending';
    const XML_PATH_STATUS_PENDING_BANKTRANSFER = 'payment/mollie_methods_banktransfer/order_status_pending';
    const XML_PATH_BANKTRANSFER_DUE_DAYS = 'payment/mollie_methods_banktransfer/due_days';
    const XML_PATH_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';
    const XML_PATH_LOCALE = 'payment/mollie_general/locale';
    const XML_PATH_IMAGES = 'payment/mollie_general/payment_images';
    const XML_PATH_USE_BASE_CURRENCY = 'payment/mollie_general/currency';
    const XML_PATH_SHOW_TRANSACTION_DETAILS = 'payment/mollie_general/transaction_details';
    const XML_PATH_IDEAL_ISSUER_LIST_TYPE = 'payment/mollie_methods_ideal/issuer_list_type';
    const XML_PATH_GIFTCARD_ISSUER_LIST_TYPE = 'payment/mollie_methods_giftcard/issuer_list_type';

    /**
     * @var ProductMetadataInterface
     */
    private $metadata;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Config
     */
    private $resourceConfig;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;
    /**
     * @var ModuleListInterface
     */
    private $moduleList;
    /**
     * @var MollieLogger
     */
    private $logger;
    /**
     * @var
     */
    private $apiCheck;
    /**
     * @var
     */
    private $apiKey;
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
        $active = $this->getStoreConfig(self::XML_PATH_MODULE_ACTIVE, $storeId);
        if (!$active) {
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
     * @return mixed
     */
    public function showTransactionDetails()
    {
        return $this->getStoreConfig(self::XML_PATH_SHOW_TRANSACTION_DETAILS);
    }

    /**
     * @param $method
     *
     * @return mixed
     */
    public function getIssuerListType($method)
    {
        if ($method == 'mollie_methods_ideal') {
            return $this->getStoreConfig(self::XML_PATH_IDEAL_ISSUER_LIST_TYPE);
        }

        if ($method == 'mollie_methods_giftcard') {
            return $this->getStoreConfig(self::XML_PATH_GIFTCARD_ISSUER_LIST_TYPE);
        }
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
     * Method code for API
     *
     * @param \Magento\Sales\Model\Order $order
     *
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
        $urlParams = '?isAjax=1';
        return $this->urlBuilder->getUrl('mollie/checkout/webhook/') . $urlParams;
    }

    /**
     * Checkout Url Builder
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->urlBuilder->getUrl('checkout/cart');
    }

    /**
     * Restart Url Builder
     *
     * @return string
     */
    public function getRestartUrl()
    {
        return $this->urlBuilder->getUrl('mollie/checkout/restart/');
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
     * Order Currency and Value array for payment request
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getOrderAmountByOrder($order)
    {
        $baseCurrency = $this->useBaseCurrency($order->getStoreId());

        if ($baseCurrency) {
            $orderAmount = [
                "currency" => $order->getBaseCurrencyCode(),
                "value"    => $this->formatCurrencyValue($order->getBaseGrandTotal(), $order->getBaseCurrencyCode())
            ];
        } else {
            $orderAmount = [
                "currency" => $order->getOrderCurrencyCode(),
                "value"    => $this->formatCurrencyValue($order->getGrandTotal(), $order->getOrderCurrencyCode())
            ];
        }

        return $orderAmount;
    }

    /**
     * @param int $storeId
     *
     * @return int
     */
    public function useBaseCurrency($storeId = 0)
    {
        return (int)$this->getStoreConfig(self::XML_PATH_USE_BASE_CURRENCY, $storeId);
    }

    /**
     * @param $value
     * @param $currency
     *
     * @return string
     */
    public function formatCurrencyValue($value, $currency)
    {
        $decimalPrecision = 2;
        if (in_array($currency, self::CURRENCIES_WITHOUT_DECIMAL)) {
            $decimalPrecision = 0;
        }

        return number_format($value, $decimalPrecision, '.', '');
    }

    /**
     * Order Currency and Value array for payment request
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array
     */
    public function getOrderAmountByQuote($quote)
    {
        $baseCurrency = $this->useBaseCurrency($quote->getStoreId());

        if ($baseCurrency) {
            $orderAmount = [
                "currency" => $quote->getBaseCurrencyCode(),
                "value"    => $this->formatCurrencyValue($quote->getBaseGrandTotal(), $quote->getBaseCurrencyCode())
            ];
        } else {
            $orderAmount = [
                "currency" => $quote->getQuoteCurrencyCode(),
                "value"    => $this->formatCurrencyValue($quote->getGrandTotal(), $quote->getQuoteCurrencyCode())
            ];
        }

        return $orderAmount;
    }

    /**
     * Determine Locale
     *
     * @param $storeId
     *
     * @return mixed|null|string
     */
    public function getLocaleCode($storeId)
    {
        $locale = $this->getStoreConfig(self::XML_PATH_LOCALE, $storeId);

        if (!$locale) {
            return null;
        }

        if ($locale == 'store') {
            $localeCode = $this->resolver->getLocale();
            if (in_array($localeCode, self::SUPPORTED_LOCAL)) {
                return $localeCode;
            } else {
                return null;
            }
        }

        return $locale;
    }

    /**
     * Returns void end date for Banktransfer payments
     *
     * @param int $storeId
     *
     * @return false|string
     */
    public function getBanktransferDueDate($storeId = 0)
    {
        $dueDays = $this->getStoreConfig(self::XML_PATH_BANKTRANSFER_DUE_DAYS, $storeId);
        if ($dueDays > 0) {
            $dueDate = new \DateTime();
            $dueDate->modify('+' . $dueDays . ' day');
            return $dueDate->format('Y-m-d');
        }
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
            'mollie_methods_inghomepay',
            'mollie_methods_giropay',
            'mollie_methods_eps',
            'mollie_methods_giftcard'
        ];

        foreach ($methodCodes as $methodCode) {
            $activePath = 'payment/' . $methodCode . '/active';
            $active = $this->getStoreConfig($activePath, $storeId);

            if ($active) {
                $maxPath = 'payment/' . $methodCode . '/max_order_total';
                $max = $this->getStoreConfig($maxPath, $storeId);
                $code = str_replace('mollie_methods_', '', $methodCode);
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
     * @param bool $addLink
     *
     * @return \Magento\Framework\Phrase
     */
    public function getPhpApiErrorMessage($addLink = true)
    {
        if ($addLink) {
            return __(
                'Mollie API client for PHP is not installed, for more information about this issue see our %1 page.',
                '<a href="https://github.com/mollie/Magento2/wiki/Troubleshooting" target="_blank">GitHub</a>'
            );
        } else {
            return __(
                'Mollie API client for PHP is not installed, for more information about this issue see: %1',
                'https://github.com/mollie/Magento2/wiki/Troubleshooting'
            );
        }
    }

    /**
     * @param array $paymentData
     *
     * @return mixed
     */
    public function validatePaymentData($paymentData)
    {
        if (isset($paymentData['billingAddress'])) {
            foreach ($paymentData['billingAddress'] as $k => $v) {
                if ((empty($v)) && ($k != 'region')) {
                    unset($paymentData['billingAddress']);
                }
            }
        }
        if (isset($paymentData['shippingAddress'])) {
            foreach ($paymentData['shippingAddress'] as $k => $v) {
                if ((empty($v)) && ($k != 'region')) {
                    unset($paymentData['shippingAddress']);
                }
            }
        }

        return $paymentData;
    }
}
