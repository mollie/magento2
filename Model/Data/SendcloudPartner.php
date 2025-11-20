<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Data;

use Mollie\Payment\Api\Data\SendcloudPartnerInterface;

class SendcloudPartner implements SendcloudPartnerInterface
{
    public function __construct(
        private string $name,
        private string $partnerId
    ) {}


    public function getName(): string
    {
        return $this->name;
    }

    public function getPartnerId(): string
    {
        return $this->partnerId;
    }
}
