<?php

namespace Mollie\Payment\Service\Mollie\Wrapper;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Exceptions\IncompatiblePlatform;
use Mollie\Api\MollieApiClient;

class MollieApiClientFallbackWrapper extends MollieApiClient
{
    /**
     * @throws ApiException If there's an API error during initialization.
     * @throws IncompatiblePlatform If the platform is not compatible.
     * @return void
     */
    public function initializeEndpoints()
    {
        parent::initializeEndpoints();

        $this->orders = new OrdersEndpointWrapper($this);
        $this->payments = new PaymentEndpointWrapper($this);
    }
}
