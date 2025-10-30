<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Webapi;

interface GetCustomerOrderInterface
{
    /**
     * @param string $hash
     * @return mixed[]
     */
    public function byHash(string $hash): array;
}
