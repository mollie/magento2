<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Message\ManagerInterface;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;

class AddResultMessage
{
    public function __construct(
        private ManagerInterface $messageManager
    ) {}

    public function execute(GetMollieStatusResult $result): void
    {
        if ($result->getStatus() == 'canceled') {
            $this->messageManager->addNoticeMessage(__('Payment canceled, please try again.'));

            return;
        }

        $this->messageManager->addErrorMessage(__('Transaction failed. Please verify your billing information and payment method, and try again.'));
    }
}
