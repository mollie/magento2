<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

interface OrderItemMetadataInterface
{
    /**
     * @return int
     */
    public function getOrderId(): int;

    /**
     * @return int
     */
    public function getOrderItemId(): int;

    /**
     * @return string
     */
    public function getMetadata(): string;
}
