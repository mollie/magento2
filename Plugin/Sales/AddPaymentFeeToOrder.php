<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Sales;

use Magento\Sales\Api\Data\OrderExtension;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class AddPaymentFeeToOrder
{
    public function __construct(
        private OrderExtensionFactory $orderExtensionFactory
    ) {}

    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchCriteria,
    ): OrderSearchResultInterface {
        foreach ($searchCriteria->getItems() as $entity) {
            $this->addMolliePaymentFeeTo($entity);
        }

        return $searchCriteria;
    }

    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order,
    ): OrderInterface {
        $this->addMolliePaymentFeeTo($order);

        return $order;
    }

    /**
     * @param OrderInterface $entity
     */
    private function addMolliePaymentFeeTo(OrderInterface $entity): void
    {
        $extensionAttributes = $this->getExtensionAttributes($entity);
        $extensionAttributes->setMolliePaymentFee($entity->getData('mollie_payment_fee'));
        $extensionAttributes->setMolliePaymentFeeTax($entity->getData('mollie_payment_fee_tax'));
        $extensionAttributes->setBaseMolliePaymentFee($entity->getData('base_mollie_payment_fee'));
        $extensionAttributes->setBaseMolliePaymentFeeTax($entity->getData('base_mollie_payment_fee_tax'));
        $entity->setExtensionAttributes($extensionAttributes);
    }

    /**
     * @param OrderInterface $entity
     * @return OrderExtension|OrderExtensionInterface|null
     */
    private function getExtensionAttributes(OrderInterface $entity)
    {
        $extensionAttributes = $entity->getExtensionAttributes();
        if ($extensionAttributes) {
            return $extensionAttributes;
        }

        return $this->orderExtensionFactory->create();
    }
}
