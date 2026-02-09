<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder\SetAddressesOnCart;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder\SetShippingOnCart;

class ConvertComponentsPaymentToOrder
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartInterfaceFactory $cartFactory,
        private readonly CartManagementInterface $cartManagement,
        private readonly Config $config,
        private readonly Payments $molliePayments,
        private readonly SaveSendcloudMetadata $saveSendcloudMetadata,
        private readonly GetCustomerFromPayment $getCustomerFromPayment,
        private readonly PaymentTokenRepositoryInterface $paymentTokenRepository,
        private readonly SetAddressesOnCart $setAddressesOnCart,
        private readonly SetShippingOnCart $setShippingOnCart,
    ) {}

    public function execute(CartInterface $baseCart, Payment $payment): OrderInterface
    {
        $this->config->addToLog('Converting express payment to order', [
            'payment_id' => $payment->id,
        ]);

        $cart = $this->cartFactory->create();
        $cart->setStoreId($baseCart->getStoreId());
        $cart->setCustomer($this->getCustomer($baseCart, $payment));

        $this->setAddressesOnCart->execute($baseCart, $cart, $payment);
        $this->setShippingOnCart->execute($cart, $payment);
        $this->cartRepository->save($cart);

        $cart->getPayment()->setMethod('mollie_methods_expresscomponents');
        $this->cartRepository->save($cart);

        $order = $this->cartManagement->submit($cart);
        $this->saveSendcloudMetadata->execute($payment, $order);
        $order->setMollieTransactionId($payment->id);
        $this->updatePaymentToken($baseCart, $order->getEntityId());

        $this->molliePayments->processResponse($order, $payment);

        $this->config->addToLog('Converted express payment to order', [
            'cart_id' => $cart->getId(),
            'payment_id' => $payment->id,
            'order_id' => $order->getId(),
        ]);

        return $order;
    }

    public function updatePaymentToken(CartInterface $baseCart, int $entityId): void
    {
        $tokens = $this->paymentTokenRepository->getByCart($baseCart);
        /** @var PaymentTokenInterface $token */
        foreach ($tokens->getItems() as $token) {
            $token->setOrderId($entityId);
            $this->paymentTokenRepository->save($token);
        }
    }

    private function getCustomer(CartInterface $oldCart, Payment $payment): CustomerInterface
    {
        /** @var CustomerInterface $customer */
        $customer = $oldCart->getCustomer();
        if ($customer->getId() !== null) {
            return $customer;
        }

        return $this->getCustomerFromPayment->execute($oldCart, $payment);
    }
}
