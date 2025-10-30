<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Helper\Data;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\Mollie;

class StartTransaction
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private Data $paymentHelper,
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private \Mollie\Payment\Service\Mollie\StartTransaction $startTransaction,
    ) {
    }

    public function byPaymentToken($token): ?string
    {
        $tokenModel = $this->paymentTokenRepository->getByToken($token);
        if (!$tokenModel) {
            throw new NoSuchEntityException(__('There is no order found with token %1', $token));
        }

        $order = $this->orderRepository->get($tokenModel->getOrderId());

        return $this->startTransactionForOrder($order);
    }

    public function byIncrementId($incrementId): ?string
    {
        $order = $this->getOrderByIncrementId($incrementId);

        return $this->startTransactionForOrder($order);
    }

    private function getOrderByIncrementId($incrementId): ?OrderInterface
    {
        $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId);

        $items = $this->orderRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        return array_shift($items);
    }

    private function startTransactionForOrder(?OrderInterface $order = null): ?string
    {
        if (!$order) {
            return null;
        }

        $paymentMethod = $this->paymentHelper->getMethodInstance($order->getPayment()->getMethod());

        if (!$paymentMethod instanceof Mollie) {
            return null;
        }

        return $this->startTransaction->execute($order);
    }
}
