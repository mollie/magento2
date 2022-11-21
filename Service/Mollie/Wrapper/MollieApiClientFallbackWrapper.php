<?php

namespace Mollie\Payment\Service\Mollie\Wrapper;

use Mollie\Api\MollieApiClient;

class MollieApiClientFallbackWrapper extends MollieApiClient
{
    public function initializeEndpoints()
    {
        parent::initializeEndpoints();

        $this->orders = new OrdersEndpointWrapper($this);
        $this->payments = new PaymentEndpointWrapper($this);
    }
}
