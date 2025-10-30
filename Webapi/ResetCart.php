<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Webapi;

use Magento\Checkout\Model\Session;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Webapi\ResetCartInterface;

class ResetCart implements ResetCartInterface
{
    public function __construct(
        private Encryptor $encryptor,
        private OrderRepositoryInterface $orderRepository,
        private Session $checkoutSession
    ) {}

    public function byHash(string $hash): void
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $decodedHash = base64_decode($hash);
        $orderId = $this->encryptor->decrypt($decodedHash);

        $this->checkIfLastRealOrder((int) $orderId);
        $this->checkoutSession->restoreQuote();
    }

    private function checkIfLastRealOrder(int $orderId): void
    {
        if ($this->checkoutSession->getLastRealOrder()->getId()) {
            return;
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        } catch (NoSuchEntityException $exception) {
            //
        }
    }
}
