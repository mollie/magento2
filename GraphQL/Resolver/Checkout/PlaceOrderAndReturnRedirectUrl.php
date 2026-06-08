<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Checkout;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Service\Mollie\StartTransaction;

class PlaceOrderAndReturnRedirectUrl implements ResolverInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private StartTransaction $startTransaction
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $order = $this->getOrderByIncrementId($value['order_id']);
        if (!$order) {
            return null;
        }

        if (strstr($order->getPayment()->getMethod(), 'mollie_methods') === false) {
            return null;
        }

        if ($order->getPayment()->getAdditionalInformation('checkout_url')) {
            return $order->getPayment()->getAdditionalInformation('checkout_url');
        }

        return $this->startTransaction->execute($order);
    }

    private function getOrderByIncrementId(string $incrementId): ?OrderInterface
    {
        $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId);

        $items = $this->orderRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        return array_shift($items);
    }
}
