<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Webapi;

interface ResetCartInterface
{
    /**
     * @param string $hash
     * @return void
     */
    public function byHash(string $hash): void;
}
