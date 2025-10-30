<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Exception;
use Mollie\Payment\Config;

class LogException
{
    private array $messagesToSkip = [
        'The \'billingAddress.familyName\' field contains characters that are not allowed',
    ];

    public function __construct(
        private Config $config,
        array $messagesToSkip = [],
    ) {
        $this->messagesToSkip = array_merge($this->messagesToSkip, $messagesToSkip);
    }

    public function execute(Exception $exception): void
    {
        $message = method_exists($exception, 'getPlainMessage') ?
            $exception->getPlainMessage() :
            $exception->getMessage();

        if (in_array($message, $this->messagesToSkip)) {
            return;
        }

        $this->config->addTolog('error', $message);
    }
}
