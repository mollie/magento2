<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service\Mollie;

use Mollie\Payment\Service\Mollie\GetMollieStatus;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;

class GetMollieStatusFake extends GetMollieStatus
{
    /**
     * @var null|GetMollieStatusResult
     */
    private $response = null;

    public function setResponse(GetMollieStatusResult $response): void
    {
        $this->response = $response;
    }

    public function execute(int $orderId, ?string $transactionId = null): GetMollieStatusResult
    {
        if ($this->response) {
            return $this->response;
        }

        return parent::execute($orderId, $transactionId);
    }
}
