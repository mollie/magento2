<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const GENERAL_VERSION = 'payment/mollie_general/version';
    const GENERAL_TYPE = 'payment/mollie_general/type';
    const GENERAL_PROFILEID = 'payment/mollie_general/profileid';
    const GENERAL_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';
    const GENERAL_ORDER_STATUS_PENDING = 'payment/mollie_general/order_status_pending';
    const GENERAL_DEFAULT_SELECTED_METHOD = 'payment/mollie_general/default_selected_method';
    const GENERAL_ENABLE_SECOND_CHANCE_EMAIL = 'payment/mollie_general/enable_second_chance_email';
    const GENERAL_SECOND_CHANCE_EMAIL_TEMPLATE = 'payment/mollie_general/second_chance_email_template';
    const PAYMENT_METHOD_PAYMENT_TITLE = 'payment/mollie_methods_%s/title';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_TYPE = 'payment/mollie_methods_%s/payment_surcharge_type';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_FIXED_AMOUNT = 'payment/mollie_methods_%s/payment_surcharge_fixed_amount';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_PERCENTAGE = 'payment/mollie_methods_%s/payment_surcharge_percentage';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_LIMIT = 'payment/mollie_methods_%s/payment_surcharge_limit';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_TAX_CLASS = 'payment/mollie_methods_%s/payment_surcharge_tax_class';
    const PAYMENT_BANKTRANSFER_STATUS_PENDING = 'payment/mollie_methods_banktransfer/order_status_pending';
    const PAYMENT_CREDITCARD_USE_COMPONENTS = 'payment/mollie_methods_creditcard/use_components';
    const PAYMENT_PAYMENTLINK_NEW_STATUS = 'payment/mollie_methods_paymentlink/order_status_new';
    const PAYMENT_PAYMENTLINK_ALLOW_MARK_AS_PAID = 'payment/mollie_methods_paymentlink/allow_mark_as_paid';
    const PAYMENT_LIMONETIK_CATEGORY = 'payment/mollie_methods_limonetik/category';
    const PAYMENT_LIMONETIK_CUSTOM_ATTRIBUTE = 'payment/mollie_methods_limonetik/custom_attribute';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    public function __construct(
        ScopeConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * @param $path
     * @param $storeId
     * @return string
     */
    private function getPath($path, $storeId)
    {
        return $this->config->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $path
     * @param $storeId
     * @return bool
     */
    private function isSetFlag($path, $storeId)
    {
        return $this->config->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->getPath(static::GENERAL_VERSION, null);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function sendInvoiceEmail($storeId = null)
    {
        return $this->isSetFlag(static::GENERAL_INVOICE_NOTIFY, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function orderStatusPending($storeId = null)
    {
        return $this->getPath(static::GENERAL_ORDER_STATUS_PENDING, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isSecondChanceEmailEnabled($storeId = null)
    {
        return $this->isSetFlag(static::GENERAL_ENABLE_SECOND_CHANCE_EMAIL, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function secondChanceEmailTemplate($storeId = null)
    {
        return $this->getPath(static::GENERAL_SECOND_CHANCE_EMAIL_TEMPLATE, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function getTestmode($storeId = null)
    {
        return $this->getPath(static::GENERAL_TYPE, $storeId) == 'test';
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getProfileId($storeId = null)
    {
        return $this->getPath(static::GENERAL_PROFILEID, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getDefaultSelectedMethod($storeId = null)
    {
        return $this->getPath(static::GENERAL_DEFAULT_SELECTED_METHOD, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function creditcardUseComponents($storeId = null)
    {
        return $this->isSetFlag(static::PAYMENT_CREDITCARD_USE_COMPONENTS, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function statusPendingBanktransfer($storeId = null)
    {
        return $this->config->getValue(
            static::PAYMENT_BANKTRANSFER_STATUS_PENDING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function statusNewPaymentLink($storeId = null)
    {
        return $this->config->getValue(
            static::PAYMENT_PAYMENTLINK_NEW_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargeType($method, $storeId = null)
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_TYPE, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargeFixedAmount($method, $storeId = null)
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_FIXED_AMOUNT, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargePercentage($method, $storeId = null)
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_PERCENTAGE, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargeLimit($method, $storeId = null)
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_LIMIT, $method), $storeId);
    }

    /**
     * @param string $method
     * @param int|null $storeId
     * @return string
     */
    public function paymentSurchargeTaxClass($method, $storeId = null)
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_SURCHARGE_TAX_CLASS, $method), $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function paymentlinkAllowMarkAsPaid($storeId = null)
    {
        return $this->isSetFlag(static::PAYMENT_PAYMENTLINK_ALLOW_MARK_AS_PAID, $storeId);
    }

    /**
     * @param $method
     * @param null $storeId
     * @return string
     */
    public function getMethodTitle($method, $storeId = null)
    {
        return $this->getPath($this->addMethodToPath(static::PAYMENT_METHOD_PAYMENT_TITLE, $method), $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getLimonetikCategory($storeId = null)
    {
        return $this->getPath(static::PAYMENT_LIMONETIK_CATEGORY, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getLimonetikCustomAttribute($storeId = null)
    {
        return $this->getPath(static::PAYMENT_LIMONETIK_CUSTOM_ATTRIBUTE, $storeId);
    }

    /**
     * @param $method
     * @return string
     */
    private function addMethodToPath($path, $method)
    {
        return sprintf(
            $path,
            str_replace('mollie_methods_', '', $method)
        );
    }
}
