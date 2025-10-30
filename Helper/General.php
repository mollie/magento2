<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Helper;

use DateTime;
use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Service\Mollie\PaymentMethods;
use Mollie\Payment\Service\Order\CancelOrder;
use Mollie\Payment\Service\Order\Uncancel;

/**
 * Class General
 *
 * @package Mollie\Payment\Helper
 * @deprecated These helper classes will be removed in a future release.
 */
class General extends AbstractHelper
{
    public const CURRENCIES_WITHOUT_DECIMAL = ['JPY'];
    public const SUPPORTED_LOCAL = [
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

    public const XML_PATH_MODULE_ACTIVE = 'payment/mollie_general/enabled';
    public const XML_PATH_API_MODUS = 'payment/mollie_general/type';
    public const XML_PATH_LIVE_APIKEY = 'payment/mollie_general/apikey_live';
    public const XML_PATH_TEST_APIKEY = 'payment/mollie_general/apikey_test';
    public const XML_PATH_DEBUG = 'payment/mollie_general/debug';
    public const XML_PATH_STATUS_PROCESSING = 'payment/mollie_general/order_status_processing';
    public const XML_PATH_BANKTRANSFER_DUE_DAYS = 'payment/mollie_methods_banktransfer/due_days';
    public const XML_PATH_INVOICE_MOMENT = 'payment/mollie_general/invoice_moment';
    public const XML_PATH_LOCALE = 'payment/mollie_general/locale';
    public const XML_PATH_IMAGES = 'payment/mollie_general/payment_images';
    public const XML_PATH_USE_BASE_CURRENCY = 'payment/mollie_general/currency';
    public const XML_PATH_ADD_QR = 'payment/mollie_methods_ideal/add_qr';
    public const XML_PATH_API_METHOD = 'payment/%method%/method';
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    private array $apiKey = [];

    public function __construct(
        Context $context,
        private StoreManagerInterface $storeManager,
        private \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        private ProductMetadataInterface $metadata,
        private Resolver $resolver,
        private MollieLogger $logger,
        private Config $config,
        private Uncancel $uncancel,
        private CancelOrder $cancelOrder,
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        parent::__construct($context);
    }

    /**
     * Availabiliy check, on Active, API Client & API Key
     *
     * @param $storeId
     *
     * @return bool
     */
    public function isAvailable($storeId): bool
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
    public function addTolog($type, $data): void
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
    public function useImage($storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getStoreConfig(self::XML_PATH_IMAGES, $storeId);
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
    public function disableExtension(): void
    {
        $this->resourceConfig->saveConfig(self::XML_PATH_MODULE_ACTIVE, 0, 'default', 0);
    }

    /**
     * @param OrderInterface $order
     *
     * @return mixed
     */
    public function getApiMethod(OrderInterface $order)
    {
        $method = $order->getPayment()->getMethod();
        $method = str_replace('_vault', '', $method);
        $methodXpath = str_replace('%method%', $method, self::XML_PATH_API_METHOD);

        return $this->getStoreConfig($methodXpath, storeId($order->getStoreId()));
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
     * @see \Mollie\Payment\Model\Adminhtml\Source\InvoiceMoment
     * @param int $storeId
     * @return string
     */
    public function getInvoiceMoment($storeId = 0)
    {
        return $this->getStoreConfig(static::XML_PATH_INVOICE_MOMENT, $storeId);
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
        if ($this->useBaseCurrency(storeId($order->getStoreId()))) {
            return $this->getAmountArray($order->getBaseCurrencyCode(), (float)$order->getBaseGrandTotal());
        }

        return $this->getAmountArray($order->getOrderCurrencyCode(), (float)$order->getGrandTotal());
    }

    public function useBaseCurrency(?int $storeId = null): int
    {
        return (int) $this->getStoreConfig(self::XML_PATH_USE_BASE_CURRENCY, $storeId);
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
            'value' => $this->formatCurrencyValue($value, $currency),
        ];
    }

    public function formatCurrencyValue(?float $value, ?string $currency): string
    {
        $decimalPrecision = 2;
        if (in_array($currency, self::CURRENCIES_WITHOUT_DECIMAL)) {
            $decimalPrecision = 0;
        }

        return number_format($value ?? 0.0, $decimalPrecision, '.', '');
    }

    /**
     * Order Currency and Value array for payment request
     */
    public function getOrderAmountByQuote(CartInterface $quote): array
    {
        if ($this->useBaseCurrency(storeId($quote->getStoreId()))) {
            return $this->getAmountArray($quote->getBaseCurrencyCode(), (float) $quote->getBaseGrandTotal());
        }

        return $this->getAmountArray($quote->getQuoteCurrencyCode(), (float) $quote->getGrandTotal());
    }

    public function getLocaleCode(?int $storeId): ?string
    {
        $locale = $this->getStoreConfig(self::XML_PATH_LOCALE, $storeId);
        if ($locale == 'store') {
            $localeCode = $this->resolver->getLocale();
            if (in_array($localeCode, self::SUPPORTED_LOCAL)) {
                $locale = $localeCode;
            }
        }

        if ($locale && $locale != 'store') {
            return $locale;
        }

        return null;
    }

    /**
     * Returns void end date for Banktransfer payments
     */
    public function getBanktransferDueDate(?int $storeId = null): string|false
    {
        $dueDays = $this->getStoreConfig(self::XML_PATH_BANKTRANSFER_DUE_DAYS, $storeId);
        if ($dueDays > 0) {
            $dueDate = new DateTime();
            $dueDate->modify('+' . $dueDays . ' day');

            return $dueDate->format('Y-m-d');
        }

        return false;
    }

    /**
     * Returns array of active methods with maximum order value
     */
    public function getAllActiveMethods(?int $storeId): array
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
        return (bool) $this->getStoreConfig('payment/' . $methodCode . '/active', $storeId);
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
     * @return Phrase
     */
    public function getPhpApiErrorMessage(bool $addLink = true)
    {
        if ($addLink) {
            return __(
                'Mollie API client for PHP is not installed, for more information about this issue see our %1 page.',
                '<a href="https://github.com/mollie/Magento2/wiki/Troubleshooting" target="_blank">GitHub</a>',
            );
        } else {
            return __(
                'Mollie API client for PHP is not installed, for more information about this issue see: %1',
                'https://github.com/mollie/Magento2/wiki/Troubleshooting',
            );
        }
    }

    public function validatePaymentData(array $paymentData): array
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
    public function uncancelOrder(OrderInterface $order): OrderInterface
    {
        try {
            $this->uncancel->execute($order);
        } catch (Exception $e) {
            $this->addTolog('error', $e->getMessage());
        }

        return $order;
    }

    /**
     * Selected pending (payment) status
     */
    public function getStatusPending(?int $storeId = 0): string
    {
        return $this->config->orderStatusPending($storeId);
    }

    /**
     * @see CancelOrder::execute()
     */
    public function registerCancellation(OrderInterface $order, $status = null): bool
    {
        return $this->cancelOrder->execute($order, $status);
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function getPendingPaymentStatus(OrderInterface $order): string
    {
        $status = null;
        $storeId = storeId($order->getStoreId());
        if ($order->getPayment()->getMethod() == 'mollie_methods_banktransfer') {
            $status = $this->config->statusPendingBanktransfer($storeId);
        }

        if (!$status) {
            $status = $this->config->orderStatusPending($storeId);
        }

        return $status;
    }
}
