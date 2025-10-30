<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsSalableWithReservationsCondition;

use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;

class DisableCheckForAdminOrders
{
    private bool $disabled = false;

    public function __construct(
        private ObjectManagerInterface $objectManager
    ) {}

    /**
     * This method is only called from the `\Mollie\Payment\Service\Order\Reorder::recreate` method, to prevent
     * any errors about no stock being available. That method cancels 1 order and recreates another so in that
     * case it's valid to not check the stock.The objectmanager here is being used as not everyone has the
     * `Magento\InventorySalesApi` module available.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->disabled = true;
    }

    public function aroundExecute($subject, $proceed, ...$arguments)
    {
        if ($this->disabled) {
            return $this->objectManager->create(ProductSalableResultInterface::class, ['errors' => []]);
        }

        return $proceed(...$arguments);
    }
}
