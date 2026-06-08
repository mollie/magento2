<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Config;

class PaymentLinkUrl
{
    public function __construct(
        private EncryptorInterface $encryptor,
        private UrlInterface $urlBuilder,
        private OrderRepositoryInterface $orderRepository,
        private Config $config,
    ) {
    }

    public function execute(int $orderId): string
    {
        $order = $this->orderRepository->get($orderId);
        $orderId = base64_encode($this->encryptor->encrypt((string) $orderId));
        $storeId = storeId($order->getStoreId());
        if ($this->config->useCustomPaymentLinkUrl($storeId)) {
            return $this->generateCustomUrl($orderId, $storeId);
        }

        return $this->urlBuilder->getUrl('mollie/checkout/paymentlink', [
            'order' => $orderId,
            '_scope' => $storeId,
        ]);
    }

    private function generateCustomUrl(string $order, ?int $storeId = null): string
    {
        $url = $this->config->customPaymentLinkUrl($storeId);

        if (stristr($url, '{{order}}')) {
            return str_replace('{{order}}', $order, $url);
        }

        return $url . $order;
    }
}
