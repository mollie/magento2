<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

if (!function_exists('storeId')) {
    /**
     * Magento marks the return of this method as int|null, but actually returns string|null.
     * This little helper fixes that and makes sure it's null|int as expected.
     *
     * @see \Magento\Sales\Api\Data\OrderInterface::getStoreId()
     */
    function storeId(string|int|null $storeId = null): ?int
    {
        if ($storeId === null) {
            return null;
        }

        return (int)$storeId;
    }
}
