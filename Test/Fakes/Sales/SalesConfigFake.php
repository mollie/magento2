<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Sales;

use Magento\Sales\Model\Config;

class SalesConfigFake extends Config
{
    private array $quoteTotals = [];

    public function withQuoteTotal(string $code, string $class): self
    {
        $this->quoteTotals[$code] = ['instance' => $class, 'sort_order' => '100'];

        return $this;
    }

    public function getGroupTotals($section, $group)
    {
        if ($section !== 'quote' || $group !== 'totals') {
            return [];
        }

        return $this->quoteTotals;
    }
}
