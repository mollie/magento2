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
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\Order\IsPaymentLinkExpired;

class PaymentLinkRedirect
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var Mollie
     */
    private $mollie;
    /**
     * @var PaymentLinkRedirectResultFactory
     */
    private $paymentLinkRedirectResultFactory;
    /**
     * @var IsPaymentLinkExpired
     */
    private $isPaymentLinkExpired;

    public function __construct(
        EncryptorInterface $encryptor,
        OrderRepositoryInterface $orderRepository,
        Mollie $mollie,
        PaymentLinkRedirectResultFactory $paymentLinkRedirectResultFactory,
        IsPaymentLinkExpired $isPaymentLinkExpired
    ) {
        $this->encryptor = $encryptor;
        $this->orderRepository = $orderRepository;
        $this->mollie = $mollie;
        $this->paymentLinkRedirectResultFactory = $paymentLinkRedirectResultFactory;
        $this->isPaymentLinkExpired = $isPaymentLinkExpired;
    }

    public function execute(string $orderId): PaymentLinkRedirectResult
    {
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
            'redirectUrl' => $this->mollie->startTransaction($order),
            'isExpired' => false,
            'alreadyPaid' => false,
        ]);
    }
}
