<?php

namespace Mollie\Payment\Service\Mollie\Wrapper;

use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\Exceptions\ApiException;

class OrdersEndpointWrapper extends OrderEndpoint
{
    use ApiKeyFallbackTrait;

    public function get($orderId, array $parameters = [])
    {
        try {
            return parent::get($orderId, $parameters);
        } catch (ApiException $exception) {
            if (!in_array($exception->getCode(), [401, 404]) || !$this->fallbackApiKeysInstance) {
                throw $exception;
            }

            if (!$this->updateClient()) {
                throw $exception;
            }

            // Retry with fallback key
            return $this->get($orderId, $parameters);
        }
    }
}
