<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Mollie\Payment\Api\Data\IssuerInterface;
use Mollie\Payment\Api\Data\MethodMetaInterface;
use Mollie\Payment\Api\Data\TerminalInterface;

class MethodMeta implements MethodMetaInterface
{
    /**
     * @param IssuerInterface[] $issuers
     * @param TerminalInterface[] $terminals
     */
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
     * @return IssuerInterface[]
     */
    public function getIssuers(): array
    {
        return $this->issuers;
    }

    /**
     * @return TerminalInterface[]
     */
    public function getTerminals(): array
    {
        return $this->terminals;
    }
}
