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
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\PaymentToken\Generate;

class PaymentToken implements ResolverInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private Generate $generate
    ) {}

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $order = $this->getOrderByIncrementId($value['order_id']);

        if (!$order) {
            return null;
        }

        if ($token = $this->paymentTokenRepository->getByOrder($order)) {
            return $token->getToken();
        }

        return $this->generate->forOrder($order)->getToken();
    }

    private function getOrderByIncrementId($incrementId): ?OrderInterface
    {
        $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId);

        $items = $this->orderRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        return array_shift($items);
    }
}
