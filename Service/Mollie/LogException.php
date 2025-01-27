<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Mollie\Payment\Config;

class LogException
{
    /**
     * @var Config
     */
    private $config;

    private $messagesToSkip = [
        'The \'billingAddress.familyName\' field contains characters that are not allowed'
    ];

    public function __construct(
        Config $config,
        array $messagesToSkip = []
    ) {
        $this->config = $config;
        $this->messagesToSkip = array_merge($this->messagesToSkip, $messagesToSkip);
    }

    public function execute(\Exception $exception): void
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
