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
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface as StoreScope;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\WebhookUrlOptions;

class Transaction
{
    private const CHECKOUT_WEBHOOK_PATH = 'mollie/checkout/webhook';
    private const EXPRESS_WEBHOOK_PATH = 'mollie/express/webhook';
    private const EXPRESS_SHIPPING_OPTIONS_PATH = 'mollie/express/shippingOptions';
    private const EXPRESS_PRODUCT_QUERY = 'placement=product';

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
    )
    {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    public function getExpressRedirectUrl(CartInterface $cart, string $paymentToken): string
    {
        $storeId = $cart->getStoreId();
        $this->urlBuilder->setScope($storeId);
        return $this->urlBuilder->getUrl(
            'mollie/express/process/',
            ['_query' => 'paymentToken=' . $paymentToken . '&storeId=' . (int)$storeId . '&utm_nooverride=1']
        );
    }

    public function getExpressWebhookUrl(CartInterface $cart): string
    {
        return $this->getExpressCallbackUrl(
            storeId($cart->getStoreId()),
            self::EXPRESS_WEBHOOK_PATH,
            'quoteId=' . $cart->getEntityId(),
        );
    }

    public function getExpressShippingOptionsUrl(CartInterface $cart): string
    {
        return $this->getExpressCallbackUrl(
            storeId($cart->getStoreId()),
            self::EXPRESS_SHIPPING_OPTIONS_PATH,
            'quoteId=' . $cart->getEntityId(),
        );
    }

    public function getExpressProductWebhookUrl(int $storeId): string
    {
        return $this->getExpressCallbackUrl($storeId, self::EXPRESS_WEBHOOK_PATH, $this->getExpressProductQuery($storeId));
    }

    public function getExpressProductShippingOptionsUrl(int $storeId): string
    {
        return $this->getExpressCallbackUrl(
            $storeId,
            self::EXPRESS_SHIPPING_OPTIONS_PATH,
            $this->getExpressProductQuery($storeId),
        );
    }

    /**
     * Store views that share a domain without store codes in their URLs all resolve to the same store, so the
     * callback carries the store id to load the right Mollie API key.
     */
    private function getExpressProductQuery(int $storeId): string
    {
        return self::EXPRESS_PRODUCT_QUERY . '&storeId=' . $storeId;
    }

    private function getExpressCallbackUrl(?int $storeId, string $path, string $query): string
    {
        if (!$this->config->isProductionMode($storeId) &&
            $this->config->useWebhooks($storeId) == WebhookUrlOptions::DISABLED) {
            return '';
        }

        $customUrl = $this->getCustomExpressCallbackUrl($storeId, $path);
        if ($customUrl !== null) {
            return $customUrl . (strpos($customUrl, '?') === false ? '?' : '&') . $query;
        }

        $this->urlBuilder->setScope($storeId);

        return $this->urlBuilder->getUrl($path . '/', [
            '_query' => $query,
        ]);
    }

    private function getCustomExpressCallbackUrl(?int $storeId, string $path): ?string
    {
        if ($this->config->useWebhooks($storeId) != WebhookUrlOptions::CUSTOM_URL) {
            return null;
        }

        $customUrl = $this->config->customWebhookUrl($storeId);
        if (stripos($customUrl, self::CHECKOUT_WEBHOOK_PATH) === false) {
            return null;
        }

        return str_ireplace(self::CHECKOUT_WEBHOOK_PATH, $path, $customUrl);
    }

    public function getRedirectUrl(OrderInterface $order, string $paymentToken): string
    {
        $storeId = storeId($order->getStoreId());
        $useCustomUrl = $this->config->useCustomRedirectUrl($storeId);
        $customUrl = $this->config->customRedirectUrl($storeId);

        if ($this->redirectUrl || ($useCustomUrl && $customUrl)) {
            return $this->addParametersToCustomUrl($order, $paymentToken, $storeId);
        }

        $parameters = 'order_id=' . (int)$order->getId() . '&payment_token=' . $paymentToken . '&utm_nooverride=1';

        $this->urlBuilder->setScope($storeId);

        return $this->urlBuilder->getUrl(
            'mollie/checkout/process/',
            ['_query' => $parameters],
        );
    }

    public function setRedirectUrl(string $url): void
    {
        $this->redirectUrl = $url;
    }

    /**
     * @param mixed[] $orders
     */
    public function getWebhookUrl(array $orders): string
    {
        foreach ($orders as $order) {
            if (!$order instanceof OrderInterface) {
                throw new InvalidArgumentException('Invalid order');
            }
        }

        $firstOrder = reset($orders);
        if ($firstOrder === false) {
            throw new InvalidArgumentException('Invalid order');
        }

        $storeId = storeId($firstOrder->getStoreId());
        if (
            !$this->config->isProductionMode($storeId) &&
            $this->config->useWebhooks($storeId) == WebhookUrlOptions::DISABLED
        ) {
            return '';
        }

        $orderIds = array_map(function (OrderInterface $order): string {
            return 'orderId[]=' . base64_encode($this->encryptor->encrypt((string)$order->getId()));
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

    private function addParametersToCustomUrl(OrderInterface $order, string $paymentToken, ?int $storeId = null): string
    {
        $replacements = [
            '{{order_id}}' => (string)$order->getId(),
            '{{increment_id}}' => (string)$order->getIncrementId(),
            '{{payment_token}}' => $paymentToken,
            '{{order_hash}}' => base64_encode($this->encryptor->encrypt((string)$order->getId())),
            '{{base_url}}' => (string)$this->scopeConfig->getValue('web/unsecure/base_url', StoreScope::SCOPE_STORE, $storeId),
            '{{unsecure_base_url}}' => (string)$this->scopeConfig->getValue('web/unsecure/base_url', StoreScope::SCOPE_STORE, $storeId),
            '{{secure_base_url}}' => (string)$this->scopeConfig->getValue('web/secure/base_url', StoreScope::SCOPE_STORE, $storeId),
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
