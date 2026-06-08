<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Magento\PaymentLinkUrl;

class SetCheckoutUrlForPaymentLink implements ObserverInterface
{
    public function __construct(
        private PaymentLinkUrl $paymentLinkUrl,
        private OrderPaymentRepositoryInterface $orderPaymentRepository
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getData('order');

        $payment = $order->getPayment();
        if ($payment->getMethod() != Paymentlink::CODE) {
            return;
        }

        $payment->setAdditionalInformation(
            'checkout_url',
            $this->paymentLinkUrl->execute((int) $order->getEntityId()),
        );

        $this->orderPaymentRepository->save($payment);
    }
}
