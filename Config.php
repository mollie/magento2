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
    const GENERAL_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';
    const XML_PATH_STATUS_PENDING_BANKTRANSFER = 'payment/mollie_methods_banktransfer/order_status_pending';
    const XML_PATH_STATUS_NEW_PAYMENT_LINK = 'payment/mollie_methods_paymentlink/order_status_new';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_TYPE = 'payment/mollie_methods_%s/payment_surcharge_type';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_FIXED_AMOUNT = 'payment/mollie_methods_%s/payment_surcharge_fixed_amount';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_PERCENTAGE = 'payment/mollie_methods_%s/payment_surcharge_percentage';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_LIMIT = 'payment/mollie_methods_%s/payment_surcharge_limit';
    const PAYMENT_METHOD_PAYMENT_SURCHARGE_TAX_CLASS = 'payment/mollie_methods_%s/payment_surcharge_tax_class';
    const PAYMENT_PAYMENTLINK_ALLOW_MARK_AS_PAID = 'payment/mollie_methods_paymentlink/allow_mark_as_paid';

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
    private function getFlag($path, $storeId)
    {
        return $this->config->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function sendInvoiceEmail($storeId = null)
    {
        return $this->getFlag(static::GENERAL_INVOICE_NOTIFY, $storeId);
    }

    public function statusPendingBanktransfer($storeId = null)
    {
        return $this->config->getValue(
            static::XML_PATH_STATUS_PENDING_BANKTRANSFER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function statusNewPaymentLink($storeId = null)
    {
        return $this->config->getValue(
            static::XML_PATH_STATUS_NEW_PAYMENT_LINK,
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
        return $this->getFlag(static::PAYMENT_PAYMENTLINK_ALLOW_MARK_AS_PAID, $storeId);
    }

    /**
     * @param $method
     * @return string
     */
    private function addMethodToPath($path, $method): string
    {
        return sprintf(
            $path,
            str_replace('mollie_methods_', '', $method)
        );
    }
}
