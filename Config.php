<?php

namespace Mollie\Payment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const XML_PATH_STATUS_PENDING_BANKTRANSFER = 'payment/mollie_methods_banktransfer/order_status_pending';
    const XML_PATH_STATUS_NEW_PAYMENT_LINK = 'payment/mollie_methods_paymentlink/order_status_new';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    public function __construct(
        ScopeConfigInterface $config
    ) {
        $this->config = $config;
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
}
