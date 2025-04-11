<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\Checkout;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\PaymentToken\Generate;

class PlaceOrderAndReturnRedirectUrl implements ResolverInterface
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
     * @var Mollie
     */
    private $mollie;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Mollie $mollie
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->mollie = $mollie;
    }

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

        $this->mollie->startTransaction($order);

        return $order->getPayment()->getAdditionalInformation('checkout_url');
    }

    /**
     * @param string $incrementId
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    private function getOrderByIncrementId($incrementId)
    {
        $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId);

        $items = $this->orderRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        return array_shift($items);
    }
}
