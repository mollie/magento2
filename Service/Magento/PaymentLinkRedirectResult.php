<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

use Mollie\Payment\Api\Data\PaymentLinkRedirectResultInterface;

class PaymentLinkRedirectResult implements PaymentLinkRedirectResultInterface
{
    /**
     * @var bool
     */
    private $alreadyPaid;
    /**
     * @var string|null
     */
    private $redirectUrl;
    /**
     * @var bool
     */
    private $isExpired;

    public function __construct(
        bool $alreadyPaid,
        bool $isExpired,
        ?string $redirectUrl = null
    ) {
        $this->alreadyPaid = $alreadyPaid;
        $this->redirectUrl = $redirectUrl;
        $this->isExpired = $isExpired;
    }

    public function isAlreadyPaid(): bool
    {
        return $this->alreadyPaid;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function isExpired(): bool
    {
        return $this->isExpired;
    }
}
