<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Mollie\Payment\Service\Mollie\Express\ExpressComponentsBeta;

/**
 * The cart page has no Mollie checkout config, so its template passes the beta setting to the Express Component.
 */
class ExpressComponents implements ArgumentInterface
{
    public function __construct(
        private readonly ExpressComponentsBeta $beta,
    ) {}

    public function isBetaEnabled(): bool
    {
        return $this->beta->isEnabled();
    }
}
