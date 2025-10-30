<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\PaymentFee\Quote\Address\Total;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Model\PaymentFee\Quote\Address\Total\PaymentFeeTax;
use Mollie\Payment\Service\PaymentFee\Calculate;
use Mollie\Payment\Service\PaymentFee\Result;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFeeTaxTest extends IntegrationTestCase
{
    public function testDoesNotApplyIfTheMethodIsNotSupported(): void
    {
        /** @var PaymentFeeTax $instance */
        $instance = $this->objectManager->create(PaymentFeeTax::class);

        /** @var Total $total */
        $total = $this->objectManager->create(Total::class);

        $quote = $this->getQuote();
        $extensionAttributes = $quote->getExtensionAttributes();
        $this->assertEquals(0, $total->getTotalAmount('tax'));
        $this->assertEquals(0, $total->getBaseTotalAmount('tax'));

        if ($extensionAttributes) {
            $this->assertEquals(0, $extensionAttributes->getMolliePaymentFeeTax());
            $this->assertEquals(0, $extensionAttributes->getBaseMolliePaymentFeeTax());
        }

        $instance->collect($quote, $this->getShippingAssignment(), $total);

        $this->assertEquals(0, $total->getTotalAmount('tax'));
        $this->assertEquals(0, $total->getBaseTotalAmount('tax'));

        if ($extensionAttributes) {
            $this->assertEquals(0, $extensionAttributes->getMolliePaymentFeeTax());
            $this->assertEquals(0, $extensionAttributes->getBaseMolliePaymentFeeTax());
        }
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_payment_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_klarna/payment_surcharge 1,95
     * @magentoConfigFixture current_store payment/mollie_methods_klarna/payment_surcharge_tax_class 2
     */
    public function testDoesApplyIfTheMethodIsSupported(): void
    {
        /** @var Result $result */
        $result = $this->objectManager->create(Result::class);
        $result->setTaxAmount(0.33);

        $calculateMock = $this->createMock(Calculate::class);
        $calculateMock->method('forCart')->willReturn($result);

        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFeeTax::class, [
            'calculate' => $calculateMock,
        ]);

        /** @var Total $total */
        $total = $this->objectManager->create(Total::class);

        $shippingAssignment = $this->getShippingAssignment();

        $shippingAssignment->setItems([
            $this->objectManager->create(CartItemInterface::class),
        ]);

        $quote = $this->getQuote('mollie_methods_klarna');
        $extensionAttributes = $quote->getExtensionAttributes();

        $this->assertEquals(0, $total->getTotalAmount('tax'));
        $this->assertEquals(0, $total->getBaseTotalAmount('tax'));

        if ($extensionAttributes) {
            $this->assertEquals(0, $extensionAttributes->getMolliePaymentFeeTax());
            $this->assertEquals(0, $extensionAttributes->getBaseMolliePaymentFeeTax());
        }

        $instance->collect($quote, $shippingAssignment, $total);

        if ($extensionAttributes) {
            $this->assertEquals(0.33, $extensionAttributes->getMolliePaymentFeeTax());
            $this->assertEquals(0.33, $extensionAttributes->getBaseMolliePaymentFeeTax());
        }
    }

    /**
     * @return ShippingAssignmentInterface
     */
    private function getShippingAssignment()
    {
        /** @var AddressInterface $address */
        $address = $this->objectManager->create(AddressInterface::class);
        $address->setQuote($this->getQuote());

        /** @var ShippingInterface $shipping */
        $shipping = $this->objectManager->create(ShippingInterface::class);
        $shipping->setAddress($address);

        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = $this->objectManager->create(ShippingAssignmentInterface::class);
        $shippingAssignment->setShipping($shipping);

        return $shippingAssignment;
    }

    /**
     * @param null $method
     * @return CartInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getQuote($method = null)
    {
        /** @var $session Session */
        $session = $this->objectManager->create(Session::class);
        $quote = $session->getQuote();

        $quote->getPayment()->setMethod($method);

        return $quote;
    }
}
