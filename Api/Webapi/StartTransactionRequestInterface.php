<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Webapi;

interface StartTransactionRequestInterface
{
    /**
     * @param string $token
     * @return string
     */
    public function execute(string $token): string;
}
