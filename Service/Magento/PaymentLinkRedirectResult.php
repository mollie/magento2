<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

class PaymentLinkRedirectResult
{
    /**
     * @var bool
     */
    private $alreadyPaid;
    /**
     * @var string|null
     */
    private $redirectUrl;

    public function __construct(
        bool $alreadyPaid,
        string $redirectUrl = null
    ) {
        $this->alreadyPaid = $alreadyPaid;
        $this->redirectUrl = $redirectUrl;
    }

    public function isAlreadyPaid(): bool
    {
        return $this->alreadyPaid;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }
}
