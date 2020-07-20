<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;

class Transaction
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Encryptor
     */
    private $encryptor;

    public function __construct(
        Config $config,
        Context $context,
        Encryptor $encryptor
    ) {
        $this->config = $config;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->encryptor = $encryptor;
    }

    /**
     * @param OrderInterface $order
     * @param string $paymentToken
     * @return string
     */
    public function getRedirectUrl(OrderInterface $order, $paymentToken)
    {
        $storeId = $order->getStoreId();
        $useCustomUrl = $this->config->useCustomRedirectUrl($storeId);
        $customUrl = $this->config->customRedirectUrl($storeId);

        if ($useCustomUrl && $customUrl) {
            return $this->addParametersToCustomUrl($order, $paymentToken, $storeId);
        }

        $parameters = 'order_id=' . intval($order->getId()) . '&payment_token=' . $paymentToken . '&utm_nooverride=1';

        $this->urlBuilder->setScope($storeId);
        return $this->urlBuilder->getUrl(
            'mollie/checkout/process/',
            ['_query' => $parameters]
        );
    }

    /**
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->urlBuilder->getUrl('mollie/checkout/webhook/', ['_query' => 'isAjax=1']);
    }

    private function addParametersToCustomUrl(OrderInterface $order, string $paymentToken, int $storeId = null)
    {
        $replacements = [
            '{{ORDER_ID}}' => $order->getId(),
            '{{INCREMENT_ID}}' => $order->getIncrementId(),
            '{{PAYMENT_TOKEN}}' => $paymentToken,
            '{{ORDER_HASH}}' => base64_encode($this->encryptor->encrypt((string)$order->getId())),
        ];

        $customUrl = $this->config->customRedirectUrl($storeId);
        $customUrl = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $customUrl
        );

        return $customUrl;
    }
}
