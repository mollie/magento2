<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

interface MethodMetaInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @return \Mollie\Payment\Api\Data\IssuerInterface[]
     */
    public function getIssuers(): array;

    /**
     * @return \Mollie\Payment\Api\Data\TerminalInterface[]
     */
    public function getTerminals(): array;
}
