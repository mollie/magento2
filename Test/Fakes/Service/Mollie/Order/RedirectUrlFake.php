<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service\Mollie\Order;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\Order\RedirectUrl;

class RedirectUrlFake extends RedirectUrl
{
    private ?Exception $exception = null;

    public function givenStartingThePaymentFails(Exception $exception): void
    {
        $this->exception = $exception;
    }

    public function execute(Mollie $methodInstance, OrderInterface $order): string
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        return parent::execute($methodInstance, $order);
    }
}
