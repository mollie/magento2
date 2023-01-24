<?php

namespace Mollie\Payment\Service\Mollie\Wrapper;

use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Exceptions\ApiException;

class PaymentEndpointWrapper extends PaymentEndpoint
{
    use ApiKeyFallbackTrait;

    public function get($paymentId, array $parameters = [])
    {
        try {
            return parent::get($paymentId, $parameters);
        } catch (ApiException $exception) {
            if ($exception->getCode() !== 401 || !$this->fallbackApiKeysInstance) {
                throw $exception;
            }

            if (!$this->updateClient()) {
                throw $exception;
            }

            // Retry with fallback key
            return $this->get($paymentId, $parameters);
        }
    }
}
