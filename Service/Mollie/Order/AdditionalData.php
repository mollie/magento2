<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Mollie\Payment\Service\Mollie\Order\AdditionalData\Details;

class AdditionalData
{
    /**
     * @var Details|null
     */
    private $details;

    public function __construct(
        ?Details $details
    ) {
        $this->details = $details;
    }

    public function getDetails(): ?Details
    {
        return $this->details;
    }
}
