<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\Mollie;

class StartTransaction
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PaymentHelper $paymentHelper,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->paymentHelper = $paymentHelper;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    public function byPaymentToken($token)
    {
        $tokenModel = $this->paymentTokenRepository->getByToken($token);
        if (!$tokenModel) {
            throw new NoSuchEntityException(__('There is no order found with token %1', $token));
        }

        $order = $this->orderRepository->get($tokenModel->getOrderId());

        return $this->startTransactionForOrder($order);
    }

    public function byIncrementId($incrementId)
    {
        $order = $this->getOrderByIncrementId($incrementId);

        return $this->startTransactionForOrder($order);
    }

    private function getOrderByIncrementId($incrementId)
    {
        $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId);

        $items = $this->orderRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        return array_shift($items);
    }

    private function startTransactionForOrder(?OrderInterface $order = null)
    {
        if (!$order) {
            return null;
        }

        $paymentMethod = $this->paymentHelper->getMethodInstance($order->getPayment()->getMethod());

        if (!$paymentMethod instanceof Mollie) {
            return null;
        }

        return $paymentMethod->startTransaction($order);
    }
}
