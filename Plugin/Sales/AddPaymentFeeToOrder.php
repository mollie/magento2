<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class AddPaymentFeeToOrder
{
    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    public function __construct(
        OrderExtensionFactory $orderExtensionFactory
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
    }

    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchCriteria
    ) {
        foreach ($searchCriteria->getItems() as $entity) {
            $this->addMolliePaymentFeeTo($entity);
        }

        return $searchCriteria;
    }

    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        $this->addMolliePaymentFeeTo($order);

        return $order;
    }

    /**
     * @param OrderInterface $entity
     */
    private function addMolliePaymentFeeTo(OrderInterface $entity)
    {
        $extensionAttributes = $entity->getExtensionAttributes() ?? $this->orderExtensionFactory->create();
        $extensionAttributes->setMolliePaymentFee($entity->getData('mollie_payment_fee'));
        $extensionAttributes->setMolliePaymentFeeTax($entity->getData('mollie_payment_fee_tax'));
        $entity->setExtensionAttributes($extensionAttributes);
    }
}
