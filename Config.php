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
    const GENERAL_TYPE = 'payment/mollie_general/type';
    const GENERAL_PROFILEID = 'payment/mollie_general/profileid';
    const GENERAL_INVOICE_NOTIFY = 'payment/mollie_general/invoice_notify';
    const PAYMENT_BANKTRANSFER_STATUS_PENDING = 'payment/mollie_methods_banktransfer/order_status_pending';
    const PAYMENT_CREDITCARD_USE_COMPONENTS = 'payment/mollie_methods_creditcard/use_components';
    const PAYMENT_KLARNAPAYLATER_PAYMENT_SURCHARGE = 'payment/mollie_methods_klarnapaylater/payment_surcharge';
    const PAYMENT_KLARNAPAYLATER_PAYMENT_SURCHARGE_TAX_CLASS = 'payment/mollie_methods_klarnapaylater/payment_surcharge_tax_class';
    const PAYMENT_KLARNASLICEIT_PAYMENT_SURCHARGE = 'payment/mollie_methods_klarnasliceit/payment_surcharge';
    const PAYMENT_KLARNASLICEIT_PAYMENT_SURCHARGE_TAX_CLASS = 'payment/mollie_methods_klarnasliceit/payment_surcharge_tax_class';
    const PAYMENT_PAYMENTLINK_NEW_STATUS = 'payment/mollie_methods_paymentlink/order_status_new';
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
    private function isSetFlag($path, $storeId)
    {
        return $this->config->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
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
     * @param int|null $storeId
     * @return string
     */
    public function klarnaPaylaterPaymentSurcharge($storeId = null)
    {
        return $this->getPath(static::PAYMENT_KLARNAPAYLATER_PAYMENT_SURCHARGE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function klarnaPaylaterPaymentSurchargeTaxClass($storeId = null)
    {
        return $this->getPath(static::PAYMENT_KLARNAPAYLATER_PAYMENT_SURCHARGE_TAX_CLASS, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function klarnaSliceitPaymentSurcharge($storeId = null)
    {
        return $this->getPath(static::PAYMENT_KLARNASLICEIT_PAYMENT_SURCHARGE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function klarnaSliceitPaymentSurchargeTaxClass($storeId = null)
    {
        return $this->getPath(static::PAYMENT_KLARNASLICEIT_PAYMENT_SURCHARGE_TAX_CLASS, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function paymentlinkAllowMarkAsPaid($storeId = null)
    {
        return $this->isSetFlag(static::PAYMENT_PAYMENTLINK_ALLOW_MARK_AS_PAID, $storeId);
    }
}
