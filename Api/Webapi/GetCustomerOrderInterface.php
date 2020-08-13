<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Webapi;

interface GetCustomerOrderInterface
{
    /**
     * @param string $hash
     * @return array
     */
    public function byHash(string $hash): array;
}