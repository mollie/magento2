<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

class TestDefaultBankTransferStatus extends AbstractSelfTest
{
    /**
     * @var \Mollie\Payment\Config
     */
    private $config;

    public function __construct(
        \Mollie\Payment\Config $config
    ) {
        $this->config = $config;
    }


    public function execute(): void
    {
        $bankTransferActive = $this->config->isMethodActive('banktransfer');
        $bankTransferStatus = $this->config->statusPendingBanktransfer();
        if ($bankTransferActive && $bankTransferStatus == 'pending_payment') {
            $message = __('Warning: We recommend to use a unique payment status for pending Banktransfer payments');
            $this->addMessage('error', $message);
        }
    }
}
