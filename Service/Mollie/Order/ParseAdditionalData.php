<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order;

use Exception;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\Order\AdditionalData\Details;
use Mollie\Payment\Service\Mollie\Order\AdditionalData\DetailsFactory;
use Mollie\Payment\Service\Mollie\Order\AdditionalData\GiftcardFactory;
use Mollie\Payment\Service\Mollie\Order\AdditionalData\MollieAmountFactory;

class ParseAdditionalData
{
    /**
     * @var AdditionalDataFactory
     */
    private $additionalDataFactory;
    /**
     * @var DetailsFactory
     */
    private $detailsFactory;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var GiftcardFactory
     */
    private $giftcardFactory;
    /**
     * @var MollieAmountFactory
     */
    private $mollieAmountFactory;

    public function __construct(
        EncryptorInterface $encryptor,
        Config $config,
        AdditionalDataFactory $additionalDataFactory,
        DetailsFactory $detailsFactory,
        GiftcardFactory $giftcardFactory,
        MollieAmountFactory $mollieAmountFactory
    ) {
        $this->additionalDataFactory = $additionalDataFactory;
        $this->detailsFactory = $detailsFactory;
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->giftcardFactory = $giftcardFactory;
        $this->mollieAmountFactory = $mollieAmountFactory;
    }

    public function fromPayment(OrderPaymentInterface $payment): AdditionalData
    {
        $details = $this->getDetails($payment);

        return $this->additionalDataFactory->create(['details' => $details]);
    }

    /**
     * @param OrderPaymentInterface $payment
     * @return array|mixed|null
     */
    public function getDetails(OrderPaymentInterface $payment): ?Details
    {
        $details = $payment->getAdditionalInformation('details');
        if ($this->config->encryptPaymentDetails()) {
            try {
                $details = $this->encryptor->decrypt($details);
            } catch (Exception $e) {}
        }
        if (is_string($details)) {
            $details = json_decode($details, true);
        }

        if (!is_array($details)) {
            return null;
        }

        if (array_key_exists('giftcards', $details)) {
            $details['giftcards'] = array_map( function (array $giftcard) {
                $giftcard['amount'] = $this->mollieAmountFactory->create($giftcard['amount']);

                return $this->giftcardFactory->create($giftcard);
            }, $details['giftcards']);
        }

        if (array_key_exists('remainderAmount', $details)) {
            $details['remainderAmount'] = $this->mollieAmountFactory->create($details['remainderAmount']);
        }

        $defaults = [
            'issuer' => null,
            'giftcards' => null,
            'remainderMethod' => null,
            'remainderAmount' => null,
        ];

        return $this->detailsFactory->create($details + $defaults);
    }
}
