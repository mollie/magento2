<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface as StoreScope;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\WebhookUrlOptions;

class Transaction
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    private ?string $redirectUrl = null;

    public function __construct(
        private Config $config,
        Context $context,
        private Encryptor $encryptor,
        private ScopeConfigInterface $scopeConfig,
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    public function setRedirectUrl(string $url): void
    {
        $this->redirectUrl = $url;
    }

    /**
     * @param OrderInterface $order
     * @param string $paymentToken
     * @return string
     */
    public function getRedirectUrl(OrderInterface $order, string $paymentToken): string
    {
        $storeId = storeId($order->getStoreId());
        $useCustomUrl = $this->config->useCustomRedirectUrl($storeId);
        $customUrl = $this->config->customRedirectUrl($storeId);

        if ($this->redirectUrl || ($useCustomUrl && $customUrl)) {
            return $this->addParametersToCustomUrl($order, $paymentToken, $storeId);
        }

        $parameters = 'order_id=' . (int) $order->getId() . '&payment_token=' . $paymentToken . '&utm_nooverride=1';

        $this->urlBuilder->setScope($storeId);

        return $this->urlBuilder->getUrl(
            'mollie/checkout/process/',
            ['_query' => $parameters],
        );
    }

    public function getWebhookUrl(array $orders): string
    {
        foreach ($orders as $order) {
            if (!$order instanceof OrderInterface) {
                throw new InvalidArgumentException('Invalid order');
            }
        }

        $firstOrder = reset($orders);
        $storeId = storeId($firstOrder->getStoreId());
        if (
            !$this->config->isProductionMode($storeId) &&
            $this->config->useWebhooks($storeId) == WebhookUrlOptions::DISABLED
        ) {
            return '';
        }

        $orderIds = array_map(function (OrderInterface $order): string {
            return 'orderId[]=' . base64_encode($this->encryptor->encrypt((string) $order->getId()));
        }, $orders);

        if ($this->config->useWebhooks($storeId) == WebhookUrlOptions::CUSTOM_URL) {
            $url = $this->config->customWebhookUrl($storeId);
            $url .= (strpos($url, '?') === false ? '?' : '&') . implode('&', $orderIds);

            return $url;
        }

        return $this->urlBuilder->getUrl('mollie/checkout/webhook/', [
            '_query' => 'isAjax=1&' . implode('&', $orderIds),
        ]);
    }

    private function addParametersToCustomUrl(OrderInterface $order, string $paymentToken, ?int $storeId = null): string|array
    {
        $replacements = [
            '{{order_id}}' => $order->getId(),
            '{{increment_id}}' => $order->getIncrementId(),
            '{{payment_token}}' => $paymentToken,
            '{{order_hash}}' => base64_encode($this->encryptor->encrypt((string) $order->getId())),
            '{{base_url}}' => $this->scopeConfig->getValue('web/unsecure/base_url', StoreScope::SCOPE_STORE, $storeId),
            '{{unsecure_base_url}}' => $this->scopeConfig->getValue('web/unsecure/base_url', StoreScope::SCOPE_STORE, $storeId),
            '{{secure_base_url}}' => $this->scopeConfig->getValue('web/secure/base_url', StoreScope::SCOPE_STORE, $storeId),
        ];

        $customUrl = $this->config->customRedirectUrl($storeId);
        if ($this->redirectUrl) {
            $customUrl = $this->redirectUrl;
        }

        $customUrl = str_ireplace(
            array_keys($replacements),
            array_values($replacements),
            $customUrl,
        );

        return $customUrl;
    }
}
