<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Payment\Helper\Data as PaymentHelper;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Payment\Config;
use Mollie\Payment\Logger\MollieLogger;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage as CouponUsage;
use Mollie\Payment\Service\Mollie\PaymentMethods;
use Mollie\Payment\Service\Order\CancelOrder;
use Mollie\Payment\Service\Order\MethodCode;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Service\Order\Uncancel;

/**
 * Class General
 *
 * @package Mollie\Payment\Helper
 * @deprecated These helper classes will be removed in a future release.
 */
class General extends AbstractHelper
{

    const MODULE_CODE = 'Mollie_Payment';
    const CURRENCIES_WITHOUT_DECIMAL = ['JPY'];
    const SUPPORTED_LOCAL = [
        'en_US',
        'ca_ES',
        'da_DK',
        'de_AT',
        'de_CH',
        'de_DE',
        'es_ES',
        'fi_FI',
        'fr_BE',
        'fr_FR',
        'hu_HU',
        'it_IT',
        'is_IS',
        'lv_LV',
        'lt_LT',
        'nb_NO',
        'nl_NL',
        'nl_BE',
        'pl_PL',
        'pt_PT',
        'sv_SE',
    ];

    const XML_PATH_MODULE_ACTIVE = 'payment/mollie_general/enabled';
    const XML_PATH_API_MODUS = 'payment/mollie_general/type';
    const XML_PATH_LIVE_APIKEY = 'payment/mollie_general/apikey_live';
    const XML_PATH_TEST_APIKEY = 'payment/mollie_general/apikey_test';
    const XML_PATH_DEBUG = 'payment/mollie_general/debug';
    // Deprecated option
    const XML_PATH_LOADING_SCREEN = 'payment/mollie_general/loading_screen';
    const XML_PATH_STATUS_PROCESSING = 'payment/mollie_general/order_status_processing';
    const XML_PATH_STATUS_PENDING = 'payment/mollie_general/order_status_pending';
    const XML_PATH_STATUS_PENDING_BANKTRANSFER = 'payment/mollie_methods_banktransfer/order_status_pending';
    const XML_PATH_BANKTRANSFER_DUE_DAYS = 'payment/mollie_methods_banktransfer/due_days';
    const XML_PATH_INVOICE_MOMENT = 'payment/mollie_general/invoice_moment';
    const XML_PATH_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';
    const XML_PATH_LOCALE = 'payment/mollie_general/locale';
    const XML_PATH_IMAGES = 'payment/mollie_general/payment_images';
    const XML_PATH_USE_BASE_CURRENCY = 'payment/mollie_general/currency';
    const XML_PATH_ADD_QR = 'payment/mollie_methods_ideal/add_qr';
    const XML_PATH_PAYMENTLINK_ADD_MESSAGE = 'payment/mollie_methods_paymentlink/add_message';
    const XML_PATH_PAYMENTLINK_MESSAGE = 'payment/mollie_methods_paymentlink/message';
    const XML_PATH_API_METHOD = 'payment/%method%/method';
    const XML_PATH_PAYMENT_DESCRIPTION = 'payment/%method%/payment_description';
    const XPATH_ISSUER_LIST_TYPE = 'payment/%method%/issuer_list_type';

    /**
     * @var ProductMetadataInterface
     */
    private $metadata;
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ResourceConfig
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
     * @var array
     */
    private $apiKey = [];
    /**
     * @var Resolver
     */
    private $resolver;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var Coupon
     */
    private $coupon;
    /**
     * @var CouponUsage
     */
    private $couponUsage;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Transaction
     */
    private $transaction;
    /**
     * @var Uncancel
     */
    private $uncancel;

    /**
     * @var CancelOrder
     */
    private $cancelOrder;

    /**
     * @var MethodCode
     */
    private $methodCode;

    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper,
        OrderRepository $orderRepository,
        StoreManagerInterface $storeManager,
        ResourceConfig $resourceConfig,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        Resolver $resolver,
        MollieLogger $logger,
        Coupon $coupon,
        CouponUsage $couponUsage,
        Config $config,
        Transaction $transaction,
        Uncancel $uncancel,
        CancelOrder $cancelOrder,
        MethodCode $methodCode
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->orderRepository = $orderRepository;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->moduleList = $moduleList;
        $this->metadata = $metadata;
        $this->resolver = $resolver;
        $this->logger = $logger;
        $this->coupon = $coupon;
        $this->couponUsage = $couponUsage;
        $this->config = $config;
        $this->transaction = $transaction;
        $this->uncancel = $uncancel;
        $this->cancelOrder = $cancelOrder;
        $this->methodCode = $methodCode;
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
        $active = $this->config->isModuleEnabled($storeId);
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
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
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
        if (array_key_exists($storeId, $this->apiKey)) {
            return $this->apiKey[$storeId];
        }

        if (empty($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $modus = $this->getModus($storeId);

        if ($modus == 'test') {
            $apiKey = trim($this->getStoreConfig(self::XML_PATH_TEST_APIKEY, $storeId) ?? '');
            if (empty($apiKey)) {
                $this->addTolog('error', 'Mollie API key not set (test modus)');
            }

            if (!preg_match('/^test_\w+$/', $apiKey)) {
                $this->addTolog('error', 'Mollie set to test modus, but API key does not start with "test_"');
            }

            $this->apiKey[$storeId] = $apiKey;
        } else {
            $apiKey = trim($this->getStoreConfig(self::XML_PATH_LIVE_APIKEY, $storeId) ?? '');
            if (empty($apiKey)) {
                $this->addTolog('error', 'Mollie API key not set (live modus)');
            }

            if (!preg_match('/^live_\w+$/', $apiKey)) {
                $this->addTolog('error', 'Mollie set to live modus, but API key does not start with "live_"');
            }
            $this->apiKey[$storeId] = $apiKey;
        }

        return $this->apiKey[$storeId];
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
     * @deprecated since 2.18.0
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
     * @deprecated See \Mollie\Payment\Config::getIssuerListType instead
     *
     * @param string $method
     *
     * @return mixed
     */
    public function getIssuerListType(string $method): string
    {
        $methodXpath = str_replace('%method%', $method, self::XPATH_ISSUER_LIST_TYPE);
        return $this->getStoreConfig($methodXpath) ?? 'none';
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function addQrOption($storeId = null)
    {
        return $this->getStoreConfig(self::XML_PATH_ADD_QR, $storeId);

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
     * @param OrderInterface $order
     *
     * @return string
     *
     * @deprecated since v2.33.0
     * @see \Mollie\Payment\Service\Order\MethodCode
     */
    public function getMethodCode($order): string
    {
        return $this->methodCode->execute($order);
    }

    /***
     * @param OrderInterface $order
     *
     * @return mixed
     */
    public function getApiMethod(OrderInterface $order)
    {
        $method = $order->getPayment()->getMethod();
        $method = str_replace('_vault', '', $method);
        $methodXpath = str_replace('%method%', $method, self::XML_PATH_API_METHOD);
        return $this->getStoreConfig($methodXpath, $order->getStoreId());
    }

    /**
     * Redirect Url Builder /w OrderId & UTM No Override
     *
     * @param $orderId
     * @param $paymentToken
     *
     * @return string
     *
     * @deprecated since 1.8.1
     * @see Transaction::getRedirectUrl
     */
    public function getRedirectUrl($orderId, $paymentToken)
    {
        $order = $this->orderRepository->get($orderId);

        return $this->transaction->getRedirectUrl($order, $paymentToken);
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
     * Selected pending (payment) status for banktransfer
     *
     * @param int $storeId
     * @deprecated
     * @see Config::statusPendingBanktransfer()
     *
     * @return mixed
     */
    public function getStatusPendingBanktransfer($storeId = 0)
    {
        return $this->config->statusPendingBanktransfer($storeId);
    }

    /**
     * @see \Mollie\Payment\Model\Adminhtml\Source\InvoiceMoment
     * @param int $storeId
     * @return string
     */
    public function getInvoiceMoment($storeId = 0)
    {
        return $this->getStoreConfig(static::XML_PATH_INVOICE_MOMENT, $storeId);
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
     * @param string $checkoutUrl
     * @param int|null $storeId
     *
     * @return mixed
     * @deprecated since 2.34.0
     */
    public function getPaymentLinkMessage($checkoutUrl, $storeId = null)
    {
        if ($this->getStoreConfig(self::XML_PATH_PAYMENTLINK_ADD_MESSAGE, $storeId)) {
            $message = $this->getStoreConfig(self::XML_PATH_PAYMENTLINK_MESSAGE, $storeId);
            return str_replace('%link%', $checkoutUrl, $message);
        }
    }

    /**
     * Order Currency and Value array for payment request
     *
     * @param OrderInterface $order
     *
     * @return array{currency: string, value: string}
     */
    public function getOrderAmountByOrder(OrderInterface $order): array
    {
        if ($this->useBaseCurrency($order->getStoreId())) {
            return $this->getAmountArray($order->getBaseCurrencyCode(), $order->getBaseGrandTotal());
        }

        return $this->getAmountArray($order->getOrderCurrencyCode(), $order->getGrandTotal());
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
     * @param string|null $currency
     * @param float|null $value
     *
     * @return array{currency: string, value: string}
     */
    public function getAmountArray(?string $currency, ?float $value): array
    {
        return [
            'currency' => $currency,
            'value'    => $this->formatCurrencyValue($value, $currency)
        ];
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

        return number_format($value ?? 0.0, $decimalPrecision, '.', '');
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
        if ($this->useBaseCurrency($quote->getStoreId())) {
            return $this->getAmountArray($quote->getBaseCurrencyCode(), $quote->getBaseGrandTotal());
        }

        return $this->getAmountArray($quote->getQuoteCurrencyCode(), $quote->getGrandTotal());
    }

    /**
     * Determine Locale
     *
     * @param        $storeId
     * @param string $method
     *
     * @return mixed|null|string
     */
    public function getLocaleCode($storeId, $method = 'payment')
    {
        $locale = $this->getStoreConfig(self::XML_PATH_LOCALE, $storeId);
        if ($locale == 'store' || (!$locale && $method == 'order')) {
            $localeCode = $this->resolver->getLocale();
            if (in_array($localeCode, self::SUPPORTED_LOCAL)) {
                $locale = $localeCode;
            }
        }

        if ($locale && $locale != 'store') {
            return $locale;
        }

        /**
         * Orders Api has a strict requirement for Locale Code,
         * so if no local is set or can be resolved en_US will be returned.
         */
        return ($method == 'order') ? 'en_US' : null;
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

        return false;
    }

    /**
     * Returns array of active methods with maximum order value
     *
     * @param $storeId
     *
     * @return array
     */
    public function getAllActiveMethods($storeId): array
    {
        $activeMethods = [];
        foreach (PaymentMethods::METHODS as $methodCode) {
            if (!$this->isMethodActive($methodCode, $storeId)) {
                continue;
            }

            $maxPath = 'payment/' . $methodCode . '/max_order_total';
            $max = $this->getStoreConfig($maxPath, $storeId);
            $code = str_replace('mollie_methods_', '', $methodCode);
            $activeMethods[$methodCode] = ['code' => $code, 'max' => $max];
        }

        return $activeMethods;
    }

    public function isMethodActive(string $methodCode, ?int $storeId = null): bool
    {
        return (bool)$this->getStoreConfig('payment/' . $methodCode . '/active', $storeId);
    }

    /**
     * Returns current version of the extension for admin display
     *
     * @return mixed
     * @deprecated since v1.12.0. The version is now determined by \Mollie\Payment\Config::getVersion.
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

    /**
     * Check whether order is paid using mollie order api
     *
     * @param Order $order
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isPaidUsingMollieOrdersApi(Order $order)
    {
        $method = $order->getPayment()->getMethod();
        try {
            $methodInstance = $this->paymentHelper->getMethodInstance($method);
        } catch (\Exception $exception) {
            return false;
        }

        if (!$methodInstance instanceof \Mollie\Payment\Model\Mollie) {
            return false;
        }

        $checkoutType = $this->getCheckoutType($order);
        if ($checkoutType != 'order') {
            return false;
        }

        return true;
    }

    /**
     * @param Order $order
     *
     * @return mixed
     */
    public function getCheckoutType(Order $order)
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();
        if (isset($additionalData['checkout_type'])) {
            return $additionalData['checkout_type'];
        }
    }

    /**
     * @param OrderInterface $order
     *
     * @return OrderInterface
     * @see Uncancel::execute()
     */
    public function uncancelOrder($order)
    {
        try {
            $this->uncancel->execute($order);
        } catch (\Exception $e) {
            $this->addTolog('error', $e->getMessage());
        }

        return $order;
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
        return $this->config->orderStatusPending($storeId);
    }

    /**
     * @see CancelOrder::execute()
     */
    public function registerCancellation(OrderInterface $order, $status = null)
    {
        return $this->cancelOrder->execute($order, $status);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @throws \Exception
     */
    public function resetCouponAfterCancellation($order)
    {
        $this->coupon->load($order->getCouponCode(), 'code');
        if ($this->coupon->getId()) {
            $this->coupon->setTimesUsed($this->coupon->getTimesUsed() - 1);
            $this->coupon->save();
            $customerId = $order->getCustomerId();
            if ($customerId) {
                $this->couponUsage->updateCustomerCouponTimesUsed($customerId, $this->coupon->getId(), false);
            }
        }
    }

    /**
     * If one of the payments has the status 'paid', return that status. Otherwise return the last status.
     *
     * @param MollieOrder $order
     * @return string|null
     */
    public function getLastRelevantStatus(MollieOrder $order)
    {
        if (!isset($order->_embedded->payments)) {
            return null;
        }

        $payments = $order->_embedded->payments;
        foreach ($payments as $payment) {
            if ($payment->status == 'paid') {
                return 'paid';
            }
        }

        return end($payments)->status;
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function getPendingPaymentStatus(OrderInterface $order)
    {
        $status = null;
        if ($order->getPayment()->getMethod() == 'mollie_methods_banktransfer') {
            $status = $this->config->statusPendingBanktransfer($order->getStoreId());
        }

        if (!$status) {
            $status = $this->config->orderStatusPending($order->getStoreId());
        }

        return $status;
    }
}
