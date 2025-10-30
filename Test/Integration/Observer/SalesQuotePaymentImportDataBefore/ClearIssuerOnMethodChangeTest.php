<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\SalesQuotePaymentImportDataBefore;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Mollie\Payment\Observer\SalesQuotePaymentImportDataBefore\ClearIssuerOnMethodChange;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ClearIssuerOnMethodChangeTest extends IntegrationTestCase
{
    public function testUnsetsThePaymentMethod(): void
    {
        /** \Magento\Quote\Api\Data\PaymentInterface $payment */
        $payment = $this->objectManager->create(PaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal');

        $payment->setAdditionalInformation('selected_issuer', 'test_issuer');

        $data = new DataObject();
        $data->setData('method', 'mollie_methods_giftcard');

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('payment', $payment);
        $observer->setData('input', $data);

        $instance = $this->objectManager->create(ClearIssuerOnMethodChange::class);
        $instance->execute($observer);

        $this->assertNull($payment->getAdditionalInformation('selected_issuer'));
    }

    public function testDoesNothingWhenTheMethodIsNotChanged(): void
    {
        /** \Magento\Quote\Api\Data\PaymentInterface $payment */
        $payment = $this->objectManager->create(PaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal');

        $payment->setAdditionalInformation('selected_issuer', 'test_issuer');

        $data = new DataObject();
        $data->setData('method', 'mollie_methods_ideal');

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('payment', $payment);
        $observer->setData('input', $data);

        $instance = $this->objectManager->create(ClearIssuerOnMethodChange::class);
        $instance->execute($observer);

        $this->assertEquals('test_issuer', $payment->getAdditionalInformation('selected_issuer'));
    }
}
