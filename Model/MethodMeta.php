<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Mollie\Payment\Api\Data\MethodMetaInterface;

class MethodMeta implements MethodMetaInterface
{
    public function __construct(
        private string $code,
        private array $issuers,
        private array $terminals
    ) {}

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string[]
     */
    public function getIssuers(): array
    {
        return $this->issuers;
    }

    /**
     * @return string[]
     */
    public function getTerminals(): array
    {
        return $this->terminals;
    }
}
