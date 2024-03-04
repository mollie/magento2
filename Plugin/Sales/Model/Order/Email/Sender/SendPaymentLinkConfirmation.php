<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Sales\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Payment\Service\Order\PaymentLinkConfirmationEmail;

class SendPaymentLinkConfirmation
{
    /**
     * @var \Mollie\Payment\Config
     */
    private $config;
    /**
     * @var PaymentLinkConfirmationEmail
     */
    private $paymentLinkConfirmationEmail;

    public function __construct(
        \Mollie\Payment\Config $config,
        PaymentLinkConfirmationEmail $paymentLinkConfirmationEmail
    ) {
        $this->config = $config;
        $this->paymentLinkConfirmationEmail = $paymentLinkConfirmationEmail;
    }

    public function aroundSend(OrderSender $subject, callable $proceed, Order $order, $forceSyncMode = false)
    {
        // When the `send()` method of the OrderSender is called, we want to call our own class instead.
        // But this class is also based on the OrderSender, so we need to check if we are not already in our own class.
        if ($order->getPayment()->getMethod() == 'mollie_methods_paymentlink' &&
            $this->config->paymentLinkUseCustomEmailTemplate((int)$order->getStoreId()) &&
            !($subject instanceof $this->paymentLinkConfirmationEmail)
        ) {
            return $this->paymentLinkConfirmationEmail->send($order);
        }

        return $proceed($order, $forceSyncMode);
    }
}
