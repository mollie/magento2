<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Mollie\Api\CompatibilityChecker;

class TestJsonExtension extends AbstractSelfTest
{
    /**
     * @var CompatibilityChecker
     */
    private $compatibilityChecker;

    public function __construct(
        CompatibilityChecker $compatibilityChecker
    ) {
        $this->compatibilityChecker = $compatibilityChecker;
    }

    public function execute(): void
    {
        if ($this->compatibilityChecker->satisfiesJsonExtension()) {
            $message = __('Success: JSON is enabled.');
            $this->addMessage('success', $message);

            return;
        }

        $message = __('Error: PHP extension JSON is not enabled. Please make sure to enable "json" in your PHP configuration.');
        $this->addMessage('error', $message);
    }
}
