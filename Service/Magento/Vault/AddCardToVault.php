<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento\Vault;

use DateInterval;
use DateTimeZone;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderPaymentExtension;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Config;

class AddCardToVault
{
    public function __construct(
        private Config $config,
        private OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        private PaymentTokenFactoryInterface $paymentTokenFactory,
        private DateTimeFactory $dateTimeFactory
    ) {}

    public function forPayment(OrderPaymentInterface $payment, Payment $molliePayment): void
    {
        if (
            !$this->config->isMagentoVaultEnabled(storeId($payment->getOrder()->getStoreId())) ||
            $payment->getMethod() !== 'mollie_methods_creditcard'
        ) {
            return;
        }

        $paymentToken = $this->getPaymentToken($molliePayment);
        $extensionAttributes = $this->getExtensionAttributes($payment);

        if ($paymentToken === null || $extensionAttributes->getVaultPaymentToken() !== null) {
            return;
        }

        $extensionAttributes->setVaultPaymentToken($paymentToken);
    }

    private function getPaymentToken(Payment $molliePayment): ?PaymentTokenInterface
    {
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
     * @return OrderPaymentExtension|OrderPaymentExtensionInterface|null
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
        $expDate = $this->dateTimeFactory->create('now', new DateTimeZone('UTC'));
        $expDate->add(new DateInterval('P1Y'));

        return $expDate->format('Y-m-d 00:00:00');
    }

    private function getCardCode(string $label): string
    {
        $cardsToCode = [
            'american express' => 'amex',
            'carta si' => 'cartasi',
        ];

        $label = strtolower($label);
        if (array_key_exists($label, $cardsToCode)) {
            return $cardsToCode[$label];
        }

        return $label;
    }
}
