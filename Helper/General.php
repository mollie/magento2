<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Math\Random as MathRandom;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\Information;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Payment\Helper\Data as PaymentHelper;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Payment\Config;
use Mollie\Payment\Logger\MollieLogger;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage as CouponUsage;
use Mollie\Payment\Service\Order\OrderCommentHistory;
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
        'nl_NL',
        'nl_BE',
        'fr_FR',
        'fr_BE',
        'de_DE',
        'de_AT',
        'de_CH',
        'es_ES',
        'ca_ES',
        'pt_PT',
        'it_IT',
        'nb_NO',
        'sv_SE',
        'fi_FI',
        'da_DK',
        'is_IS',
        'hu_HU',
        'pl_PL',
        'lv_LV',
        'lt_LT'
    ];

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
    const XML_PATH_INVOICE_MOMENT = 'payment/mollie_general/invoice_moment';
    const XML_PATH_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';
    const XML_PATH_LOCALE = 'payment/mollie_general/locale';
    const XML_PATH_IMAGES = 'payment/mollie_general/payment_images';
    const XML_PATH_USE_BASE_CURRENCY = 'payment/mollie_general/currency';
    const XML_PATH_SHOW_TRANSACTION_DETAILS = 'payment/mollie_general/transaction_details';
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
     * @var MathRandom
     */
    private $mathRandom;
    /**
     * @var Coupon
     */
    private $coupon;
    /**
     * @var CouponUsage
     */
    private $couponUsage;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
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
     * General constructor.
     *
     * @param Context                  $context
     * @param PaymentHelper            $paymentHelper
     * @param OrderRepository          $orderRepository
     * @param StoreManagerInterface    $storeManager
     * @param ResourceConfig           $resourceConfig
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $metadata
     * @param Resolver                 $resolver
     * @param MathRandom               $mathRandom
     * @param MollieLogger             $logger
     * @param Coupon                   $coupon
     * @param CouponUsage              $couponUsage
     * @param OrderCommentHistory      $orderCommentHistory
     * @param OrderManagementInterface $orderManagement
     * @param Config                   $config
     * @param Transaction              $transaction
     * @param Uncancel                 $uncancel
     */
    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper,
        OrderRepository $orderRepository,
        StoreManagerInterface $storeManager,
        ResourceConfig $resourceConfig,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        Resolver $resolver,
        MathRandom $mathRandom,
        MollieLogger $logger,
        Coupon $coupon,
        CouponUsage $couponUsage,
        OrderCommentHistory $orderCommentHistory,
        OrderManagementInterface $orderManagement,
        Config $config,
        Transaction $transaction,
        Uncancel $uncancel
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->orderRepository = $orderRepository;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->moduleList = $moduleList;
        $this->mathRandom = $mathRandom;
        $this->metadata = $metadata;
        $this->resolver = $resolver;
        $this->logger = $logger;
        $this->coupon = $coupon;
        $this->couponUsage = $couponUsage;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->orderManagement = $orderManagement;
        $this->config = $config;
        $this->transaction = $transaction;
        $this->uncancel = $uncancel;
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
        if (array_key_exists($storeId, $this->apiKey)) {
            return $this->apiKey[$storeId];
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
            $this->apiKey[$storeId] = $apiKey;
        } else {
            $apiKey = trim($this->getStoreConfig(self::XML_PATH_LIVE_APIKEY, $storeId));
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
     * @param $method
     *
     * @return mixed
     */
    public function getIssuerListType($method)
    {
        $methodXpath = str_replace('%method%', $method, self::XPATH_ISSUER_LIST_TYPE);
        return $this->getStoreConfig($methodXpath);
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
     * @param \Magento\Sales\Model\Order $order
     *
     * @return mixed
     */
    public function getMethodCode($order)
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();

        if ($method != 'mollie_methods_paymentlink') {
            $methodCode = str_replace('mollie_methods_', '', $method);
            return $methodCode;
        }
    }

    /***
     * @param \Magento\Sales\Model\Order $order
     *
     * @return mixed
     */
    public function getApiMethod($order)
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();
        $methodXpath = str_replace('%method%', $method, self::XML_PATH_API_METHOD);
        return $this->getStoreConfig($methodXpath, $order->getStoreId());
    }

    /**
     * @return mixed
     */
    public function getPaymentToken()
    {
        return $this->mathRandom->getUniqueHash();
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
     * Webhook Url Builder
     *
     * @return string
     *
     * @deprecated since 1.8.1
     * @see Transaction::getWebhookUrl()
     */
    public function getWebhookUrl()
    {
        return $this->transaction->getWebhookUrl();
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
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getOrderAmountByOrder($order)
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
     * @param $currency
     * @param $value
     *
     * @return array
     */
    public function getAmountArray($currency, $value)
    {
        return [
            "currency" => $currency,
            "value"    => $this->formatCurrencyValue($value, $currency)
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
            'mollie_methods_applepay',
            'mollie_methods_bancontact',
            'mollie_methods_banktransfer',
            'mollie_methods_belfius',
            'mollie_methods_creditcard',
            'mollie_methods_directdebit',
            'mollie_methods_eps',
            'mollie_methods_giftcard',
            'mollie_methods_giropay',
            'mollie_methods_ideal',
            'mollie_methods_kbc',
            'mollie_methods_klarnapaylater',
            'mollie_methods_klarnasliceit',
            'mollie_methods_voucher',
            'mollie_methods_mybank',
            'mollie_methods_paypal',
            'mollie_methods_paysafecard',
            'mollie_methods_przelewy24',
            'mollie_methods_sofort',
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
     * @param OrderInterface $order
     * @param null $status
     *
     * @return bool
     * @throws \Exception
     */
    public function registerCancellation(OrderInterface $order, $status = null)
    {
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $comment = __('The order was canceled');
            if ($status !== null) {
                $comment = __('The order was canceled, reason: payment %1', $status);
            }
            $this->addTolog('info', $order->getIncrementId() . ' ' . $comment);
            $this->orderCommentHistory->add($order, $comment);
            $order->getPayment()->setMessage($comment);
            $this->orderManagement->cancel($order->getId());

            if ($order->getCouponCode()) {
                $this->resetCouponAfterCancellation($order);
            }

            $this->orderRepository->save($order);

            return true;
        }

        return false;
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
     * @param $method
     * @param $orderNumber
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPaymentDescription($method, $orderNumber, $storeId = 0)
    {
        $xpath = str_replace('%method%', 'mollie_methods_' . $method, self::XML_PATH_PAYMENT_DESCRIPTION);
        $description = $this->getStoreConfig($xpath, $storeId);

        if (!trim($description)) {
            $description = '{ordernumber}';
        }

        $replacements = [
            '{ordernumber}' => $orderNumber,
            '{storename}' => $this->getStoreConfig(Information::XML_PATH_STORE_INFO_NAME, $storeId),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $description
        );
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
