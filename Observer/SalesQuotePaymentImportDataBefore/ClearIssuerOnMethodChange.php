<?php

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesQuotePaymentImportDataBefore;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ClearIssuerOnMethodChange implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Api\Data\PaymentInterface $payment */
        $payment = $observer->getData('payment');
        $paymentMethod = $payment->getMethod();

        /** @var DataObject $input */
        $input = $observer->getData('input');
        $inputMethod = $input->getData('method');

        if ($paymentMethod != $inputMethod) {
            $payment->unsAdditionalInformation('selected_issuer');
        }
    }
}
