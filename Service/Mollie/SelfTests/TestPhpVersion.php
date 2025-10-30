<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Mollie\Api\CompatibilityChecker;

class TestPhpVersion extends AbstractSelfTest
{
    public function __construct(
        private CompatibilityChecker $compatibilityChecker
    ) {}

    public function execute(): void
    {
        if ($this->compatibilityChecker->satisfiesPhpVersion()) {
            $message = __('Success: PHP version: %1.', PHP_VERSION);
            $this->addMessage('success', $message);

            return;
        }

        $minPhpVersion = $this->compatibilityChecker::MIN_PHP_VERSION;
        $message = __('Error: The client requires PHP version >= %1, you have %2.', $minPhpVersion, PHP_VERSION);
        $this->addMessage('error', $message);
    }
}
