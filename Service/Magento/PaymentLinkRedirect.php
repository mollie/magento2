<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Service\Mollie\Order\IsPaymentLinkExpired;
use Mollie\Payment\Service\Mollie\StartTransaction;

class PaymentLinkRedirect
{
    /**
     * @var PaymentLinkRedirectResultFactory
     */
    private $paymentLinkRedirectResultFactory;

    public function __construct(
        private EncryptorInterface $encryptor,
        private OrderRepositoryInterface $orderRepository,
        private StartTransaction $startTransaction,
        PaymentLinkRedirectResultFactory $paymentLinkRedirectResultFactory,
        private IsPaymentLinkExpired $isPaymentLinkExpired,
    ) {
        $this->paymentLinkRedirectResultFactory = $paymentLinkRedirectResultFactory;
    }

    public function execute(string $orderId): PaymentLinkRedirectResult
    {
        // @phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $id = $this->encryptor->decrypt(base64_decode($orderId));

        if (empty($id)) {
            throw new NotFoundException(__('Order not found'));
        }

        try {
            $order = $this->orderRepository->get($id);
        } catch (NoSuchEntityException $exception) {
            throw new NotFoundException(__('Order not found'));
        }

        if (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE])) {
            return $this->paymentLinkRedirectResultFactory->create([
                'redirectUrl' => null,
                'isExpired' => false,
                'alreadyPaid' => true,
            ]);
        }

        if ($this->isPaymentLinkExpired->execute($order)) {
            return $this->paymentLinkRedirectResultFactory->create([
                'redirectUrl' => null,
                'isExpired' => true,
                'alreadyPaid' => false,
            ]);
        }

        return $this->paymentLinkRedirectResultFactory->create([
            'redirectUrl' => $this->startTransaction->execute($order),
            'isExpired' => false,
            'alreadyPaid' => false,
        ]);
    }
}
