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
    /**
     * @var string
     */
    private $code;
    /**
     * @var array
     */
    private $issuers;
    /**
     * @var array
     */
    private $terminals;

    public function __construct(
        string $code,
        array $issuers,
        array $terminals
    ) {
        $this->code = $code;
        $this->issuers = $issuers;
        $this->terminals = $terminals;
    }

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
