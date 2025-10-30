<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Model\Adminhtml\Source\VoucherCategory;

class Config
{
    public const EXTENSION_CODE = 'Mollie_Payment';
    public const ADVANCED_INVOICE_MOMENT = 'payment/mollie_general/invoice_moment';
    public const ADVANCED_ENABLE_METHODS_API = 'payment/mollie_general/enable_methods_api';
    public const GENERAL_ENABLED = 'payment/mollie_general/enabled';
    public const GENERAL_APIKEY_LIVE = 'payment/mollie_general/apikey_live';
    public const GENERAL_APIKEY_TEST = 'payment/mollie_general/apikey_test';
    public const GENERAL_AUTOMATICALLY_SEND_SECOND_CHANCE_EMAILS = 'payment/mollie_general/automatically_send_second_chance_emails';
    public const GENERAL_DEBUG = 'payment/mollie_general/debug';
    public const GENERAL_CANCEL_FAILED_ORDERS = 'payment/mollie_general/cancel_failed_orders';
    public const GENERAL_CUSTOM_REDIRECT_URL = 'payment/mollie_general/custom_redirect_url';
    public const GENERAL_CUSTOM_WEBHOOK_URL = 'payment/mollie_general/custom_webhook_url';
    public const GENERAL_DEFAULT_SELECTED_METHOD = 'payment/mollie_general/default_selected_method';
    public const GENERAL_DASHBOARD_URL_ORDERS_API = 'payment/mollie_general/dashboard_url_orders_api';
    public const GENERAL_DASHBOARD_URL_PAYMENTS_API = 'payment/mollie_general/dashboard_url_payments_api';
    public const GENERAL_ENABLE_MAGENTO_VAULT = 'payment/mollie_general/enable_magento_vault';
    public const GENERAL_ENABLE_SECOND_CHANCE_EMAIL = 'payment/mollie_general/enable_second_chance_email';
    public const GENERAL_PROCESS_TRANSACTION_IN_THE_QUEUE = 'payment/mollie_general/process_transactions_in_the_queue';
    public const GENERAL_ENCRYPT_PAYMENT_DETAILS = 'payment/mollie_general/encrypt_payment_details';
    public const GENERAL_INCLUDE_SHIPPING_IN_SURCHARGE = 'payment/mollie_general/include_shipping_in_surcharge';
    public const GENERAL_INCLUDE_DISCOUNT_IN_SURCHARGE = 'payment/mollie_general/include_discount_in_surcharge';
    public const GENERAL_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';
    public const GENERAL_INVOICE_NOTIFY_KLARNA = 'payment/mollie_general/invoice_notify_klarna';
    public const GENERAL_LOCALE = 'payment/mollie_general/locale';
    public const GENERAL_ORDER_STATUS_PENDING = 'payment/mollie_general/order_status_pending';
    public const GENERAL_PROFILEID = 'payment/mollie_general/profileid';
    public const GENERAL_REDIRECT_WHEN_TRANSACTION_FAILS_TO = 'payment/mollie_general/redirect_when_transaction_fails_to';
    public const GENERAL_SECOND_CHANCE_EMAIL_TEMPLATE = 'payment/mollie_general/second_chance_email_template';
    public const GENERAL_SECOND_CHANCE_DELAY = 'payment/mollie_general/second_chance_email_delay';
    public const GENERAL_SECOND_CHANCE_SEND_BCC_TO = 'payment/mollie_general/second_chance_send_bcc_to';
    public const GENERAL_SECOND_CHANCE_USE_PAYMENT_METHOD = 'payment/mollie_general/second_chance_use_payment_method';
    public const GENERAL_TYPE = 'payment/mollie_general/type';
    public const GENERAL_USE_BASE_CURRENCY = 'payment/mollie_general/currency';
    public const GENERAL_USE_CUSTOM_REDIRECT_URL = 'payment/mollie_general/use_custom_redirect_url';
    public const GENERAL_USE_WEBHOOKS = 'payment/mollie_general/use_webhooks';
    public const GENERAL_VERSION = 'payment/mollie_general/version';
    public const GENERAL_ENABLE_PENDING_ORDER_CRON = 'payment/mollie_general/enable_pending_order_cron';
    public const GENERAL_PENDING_ORDER_CRON_BATCH_SIZE = 'payment/mollie_general/pending_order_cron_batch_size';
    public const PAYMENT_APPLEPAY_ENABLE_BUY_NOW_BUTTON = 'payment/mollie_methods_applepay/enable_buy_now_button';
    public const PAYMENT_APPLEPAY_BUY_NOW_BUTTON_COLOR = 'payment/mollie_methods_applepay/buy_now_button_color';
    public const PAYMENT_APPLEPAY_BUY_NOW_BUTTON_TEXT = 'payment/mollie_methods_applepay/buy_now_button_text';
    public const PAYMENT_APPLEPAY_INTEGRATION_TYPE = 'payment/mollie_methods_applepay/integration_type';
    public const PAYMENT_APPLEPAY_ENABLE_MINICART_BUTTON = 'payment/mollie_methods_applepay/enable_minicart_button';
    public const PAYMENT_APPLEPAY_MINICART_BUTTON_COLOR = 'payment/mollie_methods_applepay/minicart_button_color';
    public const PAYMENT_APPLEPAY_MINICART_BUTTON_TEXT = 'payment/mollie_methods_applepay/minicart_button_text';
    public const PAYMENT_CREDITCARD_USE_COMPONENTS = 'payment/mollie_methods_creditcard/use_components';
    public const PAYMENT_CREDITCARD_ENABLE_CUSTOMERS_API = 'payment/mollie_methods_creditcard/enable_customers_api';
    public const PAYMENT_BANKTRANSFER_STATUS_PENDING = 'payment/mollie_methods_banktransfer/order_status_pending';
    public const PAYMENT_METHOD_API_METHOD = 'payment/mollie_methods_%s/method';
    public const PAYMENT_METHOD_ISSUER_LIST_TYPE = 'payment/mollie_methods_%s/issuer_list_type';
    public const PAYMENT_METHOD_PAYMENT_ACTIVE = 'payment/mollie_methods_%s/active';
    public const PAYMENT_METHOD_PAYMENT_DESCRIPTION = 'payment/mollie_methods_%s/payment_description';
    public const PAYMENT_METHOD_CAPTURE_MODE = 'payment/mollie_methods_%s/capture_mode';
    public const PAYMENT_METHOD_PAYMENT_SURCHARGE_FIXED_AMOUNT = 'payment/mollie_methods_%s/payment_surcharge_fixed_amount';
    public const PAYMENT_METHOD_PAYMENT_SURCHARGE_LIMIT = 'payment/mollie_methods_%s/payment_surcharge_limit';
    public const PAYMENT_METHOD_PAYMENT_SURCHARGE_PERCENTAGE = 'payment/mollie_methods_%s/payment_surcharge_percentage';
    public const PAYMENT_METHOD_PAYMENT_SURCHARGE_TAX_CLASS = 'payment/mollie_methods_%s/payment_surcharge_tax_class';
    public const PAYMENT_METHOD_PAYMENT_SURCHARGE_TYPE = 'payment/mollie_methods_%s/payment_surcharge_type';
    public const PAYMENT_METHOD_PAYMENT_TITLE = 'payment/mollie_methods_%s/title';
    public const PAYMENT_PAYMENTLINK_ALLOW_MARK_AS_PAID = 'payment/mollie_methods_paymentlink/allow_mark_as_paid';
    public const PAYMENT_PAYMENTLINK_NEW_STATUS = 'payment/mollie_methods_paymentlink/order_status_new';
    public const PAYMENT_PAYMENTLINK_ADD_MESSAGE = 'payment/mollie_methods_paymentlink/add_message';
    public const PAYMENT_PAYMENTLINK_MESSAGE = 'payment/mollie_methods_paymentlink/message';
    public const PAYMENT_PAYPAL_SHOW_REFERENCE_IN_TRANSACTIONS_GRID = 'payment/mollie_methods_paypal/show_reference_in_transactions_grid';
    public const PAYMENT_USE_CUSTOM_PAYMENTLINK_URL = 'payment/mollie_general/use_custom_paymentlink_url';
    public const PAYMENT_CUSTOM_PAYMENTLINK_URL = 'payment/mollie_general/custom_paymentlink_url';
    public const PAYMENT_POINTOFSALE_ALLOWED_CUSTOMER_GROUPS = 'payment/mollie_methods_pointofsale/allowed_customer_groups';
    public const PAYMENT_VOUCHER_CATEGORY = 'payment/mollie_methods_voucher/category';
    public const PAYMENT_VOUCHER_CUSTOM_ATTRIBUTE = 'payment/mollie_methods_voucher/custom_attribute';
    public const CURRENCY_OPTIONS_DEFAULT = 'currency/options/default';

    public function __construct(
        private ScopeConfigInterface $config,
        private MollieLogger $logger,
        private Manager $moduleManager,
        private ProductMetadataInterface $productMetadata
    ) {}

    private function getPath(string $path, ?int $storeId, string $scope = ScopeInterface::SCOPE_STORE): mixed
    {
        return $this->config->getValue($path, $scope, $storeId);
    }

    private function isSetFlag(string $path, ?int $storeId, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->config->isSetFlag($path, $scope, $storeId);
    }

    /**
     * @param string $type
     * @param string|array $data
     * @return void
     */
    public function addToLog(string $type, $data): void
    {
        if (!$this->isDebugMode()) {
            return;
        }

        if ($type == 'error') {
            $this->logger->addErrorLog($type, $data);
        } else {
            $this->logger->addInfoLog($type, $data);
        }
    }

    /**
     * @return string
     */
    public function getVersion(): mixed
    {
        return $this->getPath(static::GENERAL_VERSION, null);
    }

    /**
     * Returns current version of Magento
     *
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * @return string
     */
    public function getMagentoEdition(): string
    {
        return $this->productMetadata->getEdition();
    }

    public function getStoreCurrency(?int $storeId = null): ?string
    {
        return $this->getPath(static::CURRENCY_OPTIONS_DEFAULT, $storeId);
    }

    /**
     * @return bool
     */
    public function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_ENABLED, $storeId);
    }

    public function getApiKey(?int $storeId = null): string
    {
        static $keys;

        if (isset($keys[$storeId])) {
            return $keys[$storeId];
        }

        if (!$this->isProductionMode($storeId)) {
            $apiKey = $this->getTestApiKey($storeId === null ? null : (int) $storeId);

            $keys[$storeId] = $apiKey;

            return $apiKey;
        }

        $apiKey = $this->getLiveApiKey($storeId === null ? null : (int) $storeId);

        $keys[$storeId] = $apiKey;

        return $apiKey;
    }

    public function getTestApiKey(?int $storeId = null): string
    {
        $apiKey = trim((string) $this->getPath(static::GENERAL_APIKEY_TEST, $storeId) ?? '');
        if (empty($apiKey)) {
            $this->addToLog('error', 'Mollie API key not set (test modus)');
        }

        if (!preg_match('/^test_\w+$/', $apiKey)) {
            $this->addToLog('error', 'Mollie set to test modus, but API key does not start with "test_"');
        }

        return $apiKey;
    }

    public function getLiveApiKey(?int $storeId = null): string
    {
        $apiKey = trim((string) $this->getPath(static::GENERAL_APIKEY_LIVE, $storeId) ?? '');
        if (empty($apiKey)) {
            $this->addToLog('error', 'Mollie API key not set (live modus)');
        }

        if (!preg_match('/^live_\w+$/', $apiKey)) {
            $this->addToLog('error', 'Mollie set to live modus, but API key does not start with "live_"');
        }

        return $apiKey;
    }

    public function isDebugMode(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_DEBUG, $storeId);
    }

    public function getInvoiceMoment(?int $storeId = null): ?string
    {
        return $this->getPath(static::ADVANCED_INVOICE_MOMENT, $storeId);
    }

    public function captureMode(string $method, ?int $storeId = null): ?string
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_CAPTURE_MODE, $method), $storeId);
    }

    public function isMethodsApiEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::ADVANCED_ENABLE_METHODS_API, $storeId);
    }

    public function sendInvoiceEmail(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_INVOICE_NOTIFY, $storeId);
    }

    public function sendInvoiceEmailForKlarna(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_INVOICE_NOTIFY_KLARNA, $storeId);
    }

    public function orderStatusPending(?int $storeId = null): string
    {
        return (string) $this->getPath(static::GENERAL_ORDER_STATUS_PENDING, $storeId);
    }

    public function isSecondChanceEmailEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_ENABLE_SECOND_CHANCE_EMAIL, $storeId);
    }

    public function automaticallySendSecondChanceEmails(?int $storeId = null)
    {
        if (!$this->isSecondChanceEmailEnabled($storeId)) {
            return false;
        }

        return $this->isSetFlag(static::GENERAL_AUTOMATICALLY_SEND_SECOND_CHANCE_EMAILS, $storeId);
    }

    public function includeShippingInSurcharge(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_INCLUDE_SHIPPING_IN_SURCHARGE, $storeId);
    }

    public function includeDiscountInSurcharge(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_INCLUDE_DISCOUNT_IN_SURCHARGE, $storeId);
    }

    public function secondChanceEmailTemplate(?int $storeId = null): string
    {
        return (string) $this->getPath(static::GENERAL_SECOND_CHANCE_EMAIL_TEMPLATE, $storeId);
    }

    public function secondChanceEmailDelay(?int $storeId = null): string
    {
        return (string) $this->getPath(static::GENERAL_SECOND_CHANCE_DELAY, $storeId);
    }

    /**
     * @param null|int $storeId
     * @return string|null
     */
    public function secondChanceSendBccTo(?int $storeId = null): ?string
    {
        return $this->getPath(static::GENERAL_SECOND_CHANCE_SEND_BCC_TO, $storeId);
    }

    /**
     * @param null|int $storeId
     * @return string|null
     */
    public function secondChanceUsePaymentMethod(?int $storeId = null): ?string
    {
        return $this->getPath(static::GENERAL_SECOND_CHANCE_USE_PAYMENT_METHOD, $storeId);
    }

    public function isProductionMode(?int $storeId = null): bool
    {
        return $this->getPath(static::GENERAL_TYPE, $storeId) == 'live';
    }

    public function isTestMode(?int $storeId = null): bool
    {
        return !$this->isProductionMode($storeId);
    }

    public function getProfileId(?int $storeId = null): string
    {
        return (string) $this->getPath(static::GENERAL_PROFILEID, $storeId);
    }

    public function getDefaultSelectedMethod(?int $storeId = null): string
    {
        return (string) $this->getPath(static::GENERAL_DEFAULT_SELECTED_METHOD, $storeId);
    }

    public function applePayEnableBuyNowButton(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::PAYMENT_APPLEPAY_ENABLE_BUY_NOW_BUTTON, $storeId);
    }

    public function applePayBuyNowColor(?int $storeId = null): string
    {
        return (string) $this->getPath(static::PAYMENT_APPLEPAY_BUY_NOW_BUTTON_COLOR, $storeId);
    }

    public function applePayBuyNowText(?int $storeId = null): string
    {
        return (string) $this->getPath(static::PAYMENT_APPLEPAY_BUY_NOW_BUTTON_TEXT, $storeId);
    }

    public function applePayEnableMinicartButton(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::PAYMENT_APPLEPAY_ENABLE_MINICART_BUTTON, $storeId);
    }

    public function applePayMinicartColor(?int $storeId = null): string
    {
        return (string) $this->getPath(static::PAYMENT_APPLEPAY_MINICART_BUTTON_COLOR, $storeId);
    }

    public function applePayMinicartText(?int $storeId = null): string
    {
        return (string) $this->getPath(static::PAYMENT_APPLEPAY_MINICART_BUTTON_TEXT, $storeId);
    }

    public function applePayIntegrationType(?int $storeId = null): string
    {
        return (string) $this->getPath(static::PAYMENT_APPLEPAY_INTEGRATION_TYPE, $storeId);
    }

    public function creditcardUseComponents(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::PAYMENT_CREDITCARD_USE_COMPONENTS, $storeId);
    }

    public function creditcardEnableCustomersApi(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::PAYMENT_CREDITCARD_ENABLE_CUSTOMERS_API, $storeId);
    }

    public function statusPendingBanktransfer(?int $storeId = null): string
    {
        return (string) $this->config->getValue(
            static::PAYMENT_BANKTRANSFER_STATUS_PENDING,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }

    public function statusNewPaymentLink(?int $storeId = null): string
    {
        return (string) $this->config->getValue(
            static::PAYMENT_PAYMENTLINK_NEW_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }

    public function addPaymentLinkMessage(?int $storeId = null): string
    {
        return (string) $this->getPath(
            static::PAYMENT_PAYMENTLINK_ADD_MESSAGE,
            $storeId,
        );
    }

    public function paymentLinkMessage(?int $storeId = null): string
    {
        return (string) $this->getPath(
            static::PAYMENT_PAYMENTLINK_MESSAGE,
            $storeId,
        );
    }

    public function showPaypalReferenceInTransactionsGrid(?int $storeId = null): bool
    {
        return (string) $this->isSetFlag(
            static::PAYMENT_PAYPAL_SHOW_REFERENCE_IN_TRANSACTIONS_GRID,
            $storeId,
        );
    }

    public function useCustomPaymentLinkUrl(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::PAYMENT_USE_CUSTOM_PAYMENTLINK_URL, $storeId);
    }

    public function customPaymentLinkUrl(?int $storeId = null): string
    {
        return (string) $this->getPath(
            static::PAYMENT_CUSTOM_PAYMENTLINK_URL,
            $storeId,
        );
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return bool
     */
    public function isMethodActive(string $method, ?int $storeId = null): bool
    {
        return $this->isSetFlag($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_ACTIVE, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string|null
     */
    public function paymentMethodDescription(string $method, ?int $storeId = null): mixed
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_DESCRIPTION, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargeType($method, ?int $storeId = null): mixed
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_TYPE, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargeFixedAmount($method, ?int $storeId = null): mixed
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_FIXED_AMOUNT, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargePercentage($method, ?int $storeId = null): mixed
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_PERCENTAGE, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargeLimit($method, ?int $storeId = null): mixed
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_LIMIT, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargeTaxClass($method, ?int $storeId = null): mixed
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_TAX_CLASS, $method), $storeId);
    }

    public function paymentlinkAllowMarkAsPaid(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::PAYMENT_PAYMENTLINK_ALLOW_MARK_AS_PAID, $storeId);
    }

    public function pointofsaleAllowedCustomerGroups(?int $storeId = null): string
    {
        return (string) $this->getPath(static::PAYMENT_POINTOFSALE_ALLOWED_CUSTOMER_GROUPS, $storeId);
    }

    public function getMethodTitle(string $method, ?int $storeId = null): string
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_TITLE, $method), $storeId);
    }

    public function cancelFailedOrders(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_CANCEL_FAILED_ORDERS, $storeId);
    }

    public function getDashboardUrlForPaymentsApi(?int $storeId = null): string
    {
        return (string) $this->getPath(static::GENERAL_DASHBOARD_URL_PAYMENTS_API, $storeId);
    }

    /**
     * @see VoucherCategory for possible values
     * @return string|null
     */
    public function getVoucherCategory(?int $storeId = null)
    {
        $value = $this->getPath(static::PAYMENT_VOUCHER_CATEGORY, $storeId);

        if ($value == 'null') {
            return null;
        }

        return $value;
    }

    public function getVoucherCustomAttribute(?int $storeId = null): mixed
    {
        return $this->getPath(static::PAYMENT_VOUCHER_CUSTOM_ATTRIBUTE, $storeId);
    }

    public function useBaseCurrency(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_USE_BASE_CURRENCY, $storeId);
    }

    public function useCustomRedirectUrl(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_USE_CUSTOM_REDIRECT_URL, $storeId);
    }

    public function useWebhooks(?int $storeId = null): string
    {
        return (string) $this->getPath(static::GENERAL_USE_WEBHOOKS, $storeId);
    }

    public function customWebhookUrl(?int $storeId = null): string
    {
        $value = $this->getPath(static::GENERAL_CUSTOM_WEBHOOK_URL, $storeId);

        if (!$value) {
            return '';
        }

        return $value;
    }

    public function customRedirectUrl(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return (string) $this->getPath(static::GENERAL_CUSTOM_REDIRECT_URL, $storeId, $scope);
    }

    public function redirectWhenTransactionFailsTo(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getPath(static::GENERAL_REDIRECT_WHEN_TRANSACTION_FAILS_TO, $storeId, $scope);
    }

    public function getLocale(?int $storeId = null): string
    {
        return (string) $this->getPath(static::GENERAL_LOCALE, $storeId);
    }

    public function isMagentoVaultEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_ENABLE_MAGENTO_VAULT, $storeId);
    }

    public function isMultishippingEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Mollie_Multishipping');
    }

    public function processTransactionsInTheQueue(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_PROCESS_TRANSACTION_IN_THE_QUEUE, $storeId);
    }

    public function encryptPaymentDetails(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_ENCRYPT_PAYMENT_DETAILS, $storeId);
    }

    public function getApiMethod(string $method, ?int $storeId = null): string
    {
        return (string) $this->getPath(
            $this->addMethodToPath(static::PAYMENT_METHOD_API_METHOD, $method),
            $storeId,
        );
    }

    public function getIssuerListType(string $method, ?int $storeId = null): string
    {
        return $this->getPath(
            $this->addMethodToPath(static::PAYMENT_METHOD_ISSUER_LIST_TYPE, $method),
            $storeId,
        ) ?? 'none';
    }

    public function isPendingOrderCronEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(static::GENERAL_ENABLE_PENDING_ORDER_CRON, $storeId);
    }

    public function pendingOrderCronBatchSize(?int $storeId = null): int
    {
        return (int) ($this->getPath(static::GENERAL_PENDING_ORDER_CRON_BATCH_SIZE, $storeId) ?? 25);
    }

    /**
     * @param $method
     * @return string
     */
    private function addMethodToPath($path, $method): string
    {
        return sprintf(
            $path,
            str_replace('mollie_methods_', '', $method ?? ''),
        );
    }
}
