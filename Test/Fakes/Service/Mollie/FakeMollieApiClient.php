<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Fakes\Service\Mollie;

use Mollie\Payment\Service\Mollie\MollieApiClient;

class FakeMollieApiClient extends MollieApiClient
{
    /**
     * @var \Mollie\Api\MollieApiClient
     */
    private $instance;

    public function setInstance(\Mollie\Api\MollieApiClient $instance)
    {
        $this->instance = $instance;
    }

    public function loadByStore(int $storeId = null): \Mollie\Api\MollieApiClient
    {
        if ($this->instance) {
            return $this->instance;
        }

        return parent::loadByStore($storeId);
    }
}
