<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Fakes\Service\Mollie;

use Magento\TestFramework\ObjectManager;
use Mollie\Api\Resources\Payment;
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

    public function returnFakePayment(?Payment $payment = null): ?Payment
    {
        $this->loadInstance();

        $endpoint = ObjectManager::getInstance()->create(FakeMolliePaymentApiEndpoint::class);

        $this->instance->payments = $endpoint;

        if ($payment) {
            $endpoint->setFakePayment($payment);
            return $payment;
        }

        return null;
    }
}
