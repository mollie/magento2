<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mollie\Payment\Config;
use stdClass;

class SaveAdditionalInformationDetails
{
    public function __construct(
        private EncryptorInterface $encryptor,
        private Config $config
    ) {}

    public function execute(OrderPaymentInterface $payment, ?stdClass $details): void
    {
        $details = json_encode($details);
        if ($this->config->encryptPaymentDetails()) {
            $details = $this->encryptor->encrypt($details);
        }

        $payment->setAdditionalInformation('details', $details);
    }
}
