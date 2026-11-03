<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Checkout;

use Magento\Checkout\Model\Session;

class SessionFake extends Session
{
    private bool $isQuoteRequested = false;

    public function getQuote()
    {
        $this->isQuoteRequested = true;

        return parent::getQuote();
    }

    public function isQuoteRequested(): bool
    {
        return $this->isQuoteRequested;
    }
}
