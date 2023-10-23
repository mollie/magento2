<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

class TestAppCodeInstall extends AbstractSelfTest
{
    public function execute(): void
    {
        if (stripos(__DIR__, 'app/code') !== false) {
            $message = __('Warning: We recommend to install the Mollie extension using Composer, currently it\'s installed in the app/code folder.');
            $this->addMessage('error', $message);
        }
    }
}
