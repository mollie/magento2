<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Model\Config as SalesConfig;

class ThirdPartyTotalCollectors
{
    private const SECTION = 'quote';

    private const GROUP = 'totals';

    private const FIRST_PARTY_NAMESPACES = ['Magento\\', 'Mollie\\'];

    public function __construct(
        private readonly SalesConfig $salesConfig,
    ) {}

    /**
     * @return array<string, string>
     */
    public function get(): array
    {
        return array_filter(
            array_map(
                fn (array $totalConfig): string => ltrim((string)($totalConfig['instance'] ?? ''), '\\'),
                $this->salesConfig->getGroupTotals(self::SECTION, self::GROUP),
            ),
            fn (string $class): bool => $class !== '' && !$this->isFirstParty($class),
        );
    }

    private function isFirstParty(string $class): bool
    {
        return array_filter(
            self::FIRST_PARTY_NAMESPACES,
            fn (string $namespace): bool => str_starts_with($class, $namespace),
        ) !== [];
    }
}
