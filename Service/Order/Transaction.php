<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class Transaction
{
    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ScopeConfigInterface $config,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param int $orderId
     * @param string $paymentToken
     * @param string $storeId
     * @return string
     */
    public function getRedirectUrl($orderId, $paymentToken, $storeId = null)
    {
        $useCustomUrl = $this->config->getValue('payment/mollie_general/use_custom_redirect_url', ScopeInterface::SCOPE_STORE, $storeId);
        $customUrl = $this->config->getValue('payment/mollie_general/custom_redirect_url', ScopeInterface::SCOPE_STORE, $storeId);

        if ($useCustomUrl && $customUrl) {
            return $customUrl;
        }

        return $this->urlBuilder->getUrl(
            'mollie/checkout/process/',
            ['_query' => 'order_id=' . intval($orderId) . '&payment_token=' . $paymentToken . '&utm_nooverride=1']
        );
    }

    /**
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->urlBuilder->getUrl('mollie/checkout/webhook/', ['_query' => 'isAjax=1']);
    }
}
