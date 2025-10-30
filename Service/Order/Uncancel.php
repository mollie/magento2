<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\Uncancel\OrderReservation;

class Uncancel
{
    /**
     * @var bool
     */
    private $isInventorySalesApiEnabled;

    public function __construct(
        private Config $config,
        private OrderRepositoryInterface $orderRepository,
        private OrderReservation $uncancelOrderItemReservation,
        private ManagerInterface $eventManager,
        private Manager $moduleManager
    ) {}

    public function execute(OrderInterface $order): void
    {
        $this->isInventorySalesApiEnabled = $this->moduleManager->isEnabled('Magento_InventorySalesApi');

        $this->updateOrder($order);
        $this->updateOrderItems($order);

        $this->orderRepository->save($order);
        $this->eventManager->dispatch('sales_order_uncancel', ['order' => $order]);
    }

    private function updateOrder(OrderInterface $order): void
    {
        $order->setState(Order::STATE_NEW);
        $order->addStatusToHistory(
            $this->config->orderStatusPending(storeId($order->getStoreId())),
            __('Order uncanceled by webhook.'),
            true,
        );

        $order->setSubtotalCanceled(0);
        $order->setBaseSubtotalCanceled(0);

        $order->setTaxCanceled(0);
        $order->setBaseTaxCanceled(0);

        $order->setShippingCanceled(0);
        $order->setBaseShippingCanceled(0);

        $order->setDiscountCanceled(0);
        $order->setBaseDiscountCanceled(0);

        $order->setTotalCanceled(0);
        $order->setBaseTotalCanceled(0);
    }

    private function updateOrderItems(OrderInterface $order): void
    {
        /** @var OrderItemInterface $item */
        foreach ($order->getAllItems() as $item) {
            if ($this->isInventorySalesApiEnabled) {
                $this->uncancelOrderItemReservation->execute($item);
            }

            $this->uncancelItem($item);
        }
    }

    private function uncancelItem(OrderItemInterface $item): void
    {
        $item->setQtyCanceled(0);
        $item->setTaxCanceled(0);
        $item->setDiscountTaxCompensationCanceled(0);

        $this->eventManager->dispatch('sales_order_item_uncancel', ['item' => $item]);

        /** @var OrderItemInterface $child */
        foreach ($item->getChildrenItems() as $child) {
            $child->setQtyCanceled(0);
            $child->setTaxCanceled(0);
            $child->setDiscountTaxCompensationCanceled(0);

            $this->eventManager->dispatch('sales_order_item_uncancel', ['item' => $child]);
        }
    }
}
