<?php

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mollie\Payment\Config;

class SaveAdditionalInformationDetails
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        EncryptorInterface $encryptor,
        Config $config
    ) {
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    public function execute(OrderPaymentInterface $payment, ?\stdClass $details): void
    {
        $details = json_encode($details);
        if ($this->config->encryptPaymentDetails()) {
            $details = $this->encryptor->encrypt($details);
        }

        $payment->setAdditionalInformation('details', $details);
    }
}
