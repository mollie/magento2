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
    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        EncryptorInterface $encryptor,
        UrlInterface $urlBuilder,
        OrderRepositoryInterface $orderRepository,
        Config $config
    ) {
        $this->encryptor = $encryptor;
        $this->urlBuilder = $urlBuilder;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
    }

    public function execute(int $orderId): string
    {
        $order = $this->orderRepository->get($orderId);
        $orderId = base64_encode($this->encryptor->encrypt((string)$orderId));
        if ($this->config->useCustomPaymentLinkUrl($order->getStoreId())) {
            return $this->generateCustomUrl($orderId, $order->getStoreId());
        }

        return $this->urlBuilder->getUrl('mollie/checkout/paymentlink', [
            'order' => $orderId,
            '_scope' => $order->getStoreId()
        ]);
    }

    private function generateCustomUrl(string $order, $storeId = null)
    {
        $url = $this->config->customPaymentLinkUrl($storeId);

        if (stristr($url, '{{order}}')) {
            return str_replace('{{order}}', $order, $url);
        }

        return $url . $order;
    }
}
