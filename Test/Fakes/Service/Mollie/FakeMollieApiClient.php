<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service\Mollie;

use Exception;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class FakeMollieApiClient extends MollieApiClient
{
    private ?\Mollie\Api\MollieApiClient $instance = null;

    public function setInstance(\Mollie\Api\MollieApiClient $instance): void
    {
        $this->instance = $instance;
    }

    private function loadInstance(): void
    {
        if (!$this->instance) {
            $this->instance = parent::loadByStore();
        }
    }

    public function loadByStore(?int $storeId = null): \Mollie\Api\MollieApiClient
    {
        if ($this->instance) {
            return $this->instance;
        }

        return parent::loadByStore($storeId);
    }

    public function loadByApiKey(string $apiKey): \Mollie\Api\MollieApiClient
    {
        if ($this->instance) {
            return $this->instance;
        }

        return parent::loadByApiKey($apiKey);
    }

    public function returnFakePayment(?Payment $payment = null): ?Payment
    {
        $this->loadInstance();

        throw new Exception('TODO: Implement this');
    }
}
