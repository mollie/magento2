<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use stdClass;

class ValidateMetadata
{
    private bool $skipValidation = false;

    public function __construct(
        private Config $config
    ) {}

    public function skipValidation(): void
    {
        $this->skipValidation = true;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(?stdClass $metadata = null, ?OrderInterface $order = null): void
    {
        if ($this->skipValidation || $order === null) {
            return;
        }

        if (isset($metadata->order_id)) {
            $this->validateSingleOrder($metadata, $order);

            return;
        }

        if (isset($metadata->order_ids)) {
            $this->validateMulitpleOrders($metadata, $order);

            return;
        }

        $this->config->addTolog('error', 'No metadata found for order ' . $order->getEntityId());
        throw new LocalizedException(__('No metadata found for order %1', $order->getEntityId()));
    }

    private function validateSingleOrder(stdClass $metadata, OrderInterface $order): void
    {
        // Single order
        if ($metadata->order_id != $order->getEntityId()) {
            $this->config->addTolog(
                'error',
                'Order ID does not match. Mollie: ' . $metadata->order_id . ' ' .
                'Magento: ' . $order->getEntityId(),
            );

            throw new LocalizedException(__('Order ID does not match'));
        }
    }

    private function validateMulitpleOrders(stdClass $metadata, OrderInterface $order): void
    {
        // Multiple orders (Multishipping)
        $orderIds = explode(', ', $metadata->order_ids);
        if (!in_array($order->getEntityId(), $orderIds)) {
            $this->config->addTolog(
                'error',
                'Order ID does not match. Mollie: ' . $metadata->order_ids . ' ' .
                'Magento: ' . $order->getEntityId(),
            );

            throw new LocalizedException(__('Order ID does not match'));
        }
    }
}
