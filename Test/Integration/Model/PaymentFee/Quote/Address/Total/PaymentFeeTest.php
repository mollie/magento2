<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\PaymentFee\Quote\Address\Total;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Service\PaymentFee\Calculate;
use Mollie\Payment\Service\PaymentFee\Result;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFeeTest extends IntegrationTestCase
{
    public function testDoesNotApplyIfTheMethodIsNotSupported()
    {
        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        /** @var Total $total */
        $total = $this->objectManager->create(Total::class);

        $quote = $this->getQuote();
        $this->assertEquals(0, $total->getTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $total->getBaseTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $quote->getExtensionAttributes()->getMolliePaymentFee());
        $this->assertEquals(0, $quote->getExtensionAttributes()->getBaseMolliePaymentFee());

        $instance->collect($quote, $this->getShippingAssignment(), $total);

        $this->assertEquals(0, $total->getTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $total->getBaseTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $quote->getExtensionAttributes()->getMolliePaymentFee());
        $this->assertEquals(0, $quote->getExtensionAttributes()->getBaseMolliePaymentFee());
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_payment_saved.php
     */
    public function testDoesApplyIfTheMethodIsSupported()
    {
        /** @var Result $result */
        $result = $this->objectManager->create(Result::class);
        $result->setAmount(1.61);

        $calculateMock = $this->createMock(Calculate::class);
        $calculateMock->method('forCart')->willReturn($result);

        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class, [
            'calculate' => $calculateMock,
        ]);

        /** @var Total $total */
        $total = $this->objectManager->create(Total::class);

        $shippingAssignment = $this->getShippingAssignment();

        $shippingAssignment->setItems([
            $this->objectManager->create(CartItemInterface::class),
        ]);

        $quote = $this->getQuote('mollie_methods_klarnapaylater');

        $this->assertEquals(0, $total->getTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $total->getBaseTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $quote->getExtensionAttributes()->getMolliePaymentFee());
        $this->assertEquals(0, $quote->getExtensionAttributes()->getBaseMolliePaymentFee());

        $instance->collect($quote, $shippingAssignment, $total);

        $this->assertEquals(1.61, $total->getTotalAmount('mollie_payment_fee'));
        $this->assertEquals(1.61, $total->getBaseTotalAmount('mollie_payment_fee'));
        $this->assertEquals(1.61, $quote->getExtensionAttributes()->getMolliePaymentFee());
        $this->assertEquals(1.61, $quote->getExtensionAttributes()->getBaseMolliePaymentFee());
    }

    /**
     * @return ShippingAssignmentInterface
     */
    private function getShippingAssignment()
    {
        /** @var AddressInterface $address */
        $address = $this->objectManager->create(AddressInterface::class);

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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getQuote($method = null)
    {
        /** @var $session \Magento\Checkout\Model\Session  */
        $session = $this->objectManager->create(Session::class);
        $quote = $session->getQuote();

        $quote->getPayment()->setMethod($method);

        return $quote;
    }
}
