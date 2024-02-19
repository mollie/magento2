<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

class GetMollieStatusResult
{
    /**
     * @var string
     */
    private $status;
    /**
     * @var string
     */
    private $method;

    public function __construct(
        string $status,
        string $method
    ) {
        $this->status = $status;
        $this->method = $method;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
