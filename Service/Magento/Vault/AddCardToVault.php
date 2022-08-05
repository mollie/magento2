<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Magento\Vault;

use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Mollie\Api\Resources\Order;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Config;

class AddCardToVault
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(
        Config $config,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->config = $config;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function forPayment(OrderPaymentInterface $payment, Order $mollieOrder)
    {
        if (!$this->config->isMagentoVaultEnabled($payment->getOrder()->getStoreId()) ||
            $payment->getMethod() !== 'mollie_methods_creditcard'
        ) {
            return;
        }

        $paymentToken = $this->getPaymentToken($mollieOrder);
        $extensionAttributes = $this->getExtensionAttributes($payment);

        if ($paymentToken === null || $extensionAttributes->getVaultPaymentToken() !== null) {
            return;
        }

        $extensionAttributes->setVaultPaymentToken($paymentToken);
    }

    private function getPaymentToken(Order $mollieOrder): ?PaymentTokenInterface
    {
        /** @var Payment $molliePayment */
        $molliePayment = $mollieOrder->payments()->offsetGet(0);
        $details = $molliePayment->details;

        if (!$details || !isset($details->cardLabel) || !isset($details->cardNumber)) {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($molliePayment->mandateId);
        $paymentToken->setExpiresAt($this->getExpirationDate());

        $paymentToken->setTokenDetails(json_encode([
            'type' => $this->getCardCode($details->cardLabel),
            'name' => $details->cardLabel,
            'maskedCC' => $details->cardNumber,
        ]));

        return $paymentToken;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @return \Magento\Sales\Api\Data\OrderPaymentExtension|\Magento\Sales\Api\Data\OrderPaymentExtensionInterface|null
     */
    private function getExtensionAttributes(OrderPaymentInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    private function getExpirationDate(): string
    {
        $expDate = $this->dateTimeFactory->create('now', new \DateTimeZone('UTC'));
        $expDate->add(new \DateInterval('P1Y'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    private function getCardCode(string $label): string
    {
        $cardsToCode = [
            'american express' => 'amex',
            'carta si' => 'cartasi',
//            'carte bleue' => '',
//            'dankort' => '',
//            'diners club' => '',
//            'discover' => '',
//            'jcb' => '',
//            'laser' => '',
//            'unionpay' => '',
        ];

        $label = strtolower($label);
        if (array_key_exists($label, $cardsToCode)) {
            return $cardsToCode[$label];
        }

        return $label;
    }
}
