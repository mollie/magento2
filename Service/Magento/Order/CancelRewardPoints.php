<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Magento\Order;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Reward\Observer\ReturnRewardPoints;
use Magento\Sales\Api\Data\OrderInterface;

class CancelRewardPoints
{
    /**
     * @var Manager
     */
    private $moduleManager;
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    )
    {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
    }

    /**
     * Magento Reward point has an even to listen for canceled orders, but this is only active in the adminhtml area.
     * So mock the event so that the reward points get returned when the order gets canceled in the frontend.
     */
    public function execute(OrderInterface $order): void
    {
        if (!$this->moduleManager->isEnabled('Magento_Reward')) {
            return;
        }

        $data = ['order' => $order];
        $eventName = 'order_cancel_after';

        // Magento doesn't use factories so neither are we. Plus, using factories gives dependency issues.
        $event = new Event($data);
        $event->setName($eventName);

        $wrapper = new Observer();
        $wrapper->setData(array_merge(['event' => $event], $data));

        // @phpstan-ignore-next-line
        $instance = $this->objectManager->create(ReturnRewardPoints::class);
        $instance->execute($wrapper);
    }
}
