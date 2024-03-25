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
        $method = str_replace('mollie_methods_', '', $method);

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

    public function shouldRedirectToSuccessPage(): bool
    {
        $status = $this->status;
        if (in_array($status, ['created', 'open']) && $this->method == 'banktransfer') {
            return true;
        }

        return in_array($status, ['pending', 'paid', 'authorized']);
    }
}
