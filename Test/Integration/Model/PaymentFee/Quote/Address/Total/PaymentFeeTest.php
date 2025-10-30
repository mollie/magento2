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
use Mollie\Payment\Model\PaymentFee\Quote\Address\Total\PaymentFee;
use Mollie\Payment\Service\PaymentFee\Calculate;
use Mollie\Payment\Service\PaymentFee\Result;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFeeTest extends IntegrationTestCase
{
    public function testDoesNotApplyIfTheMethodIsNotSupported(): void
    {
        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        /** @var Total $total */
        $total = $this->objectManager->create(Total::class);

        $quote = $this->getQuote();
        $extensionAttributes = $quote->getExtensionAttributes();

        $this->assertEquals(0, $total->getTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $total->getBaseTotalAmount('mollie_payment_fee'));

        if ($extensionAttributes) {
            $this->assertEquals(0, $extensionAttributes->getMolliePaymentFee());
            $this->assertEquals(0, $extensionAttributes->getBaseMolliePaymentFee());
        }

        $instance->collect($quote, $this->getShippingAssignment(), $total);

        $this->assertEquals(0, $total->getTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $total->getBaseTotalAmount('mollie_payment_fee'));

        if ($extensionAttributes) {
            $this->assertEquals(0, $extensionAttributes->getMolliePaymentFee());
            $this->assertEquals(0, $extensionAttributes->getBaseMolliePaymentFee());
        }
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_payment_saved.php
     */
    public function testDoesApplyIfTheMethodIsSupported(): void
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

        $quote = $this->getQuote('mollie_methods_klarna');
        $extensionAttributes = $quote->getExtensionAttributes();

        $this->assertEquals(0, $total->getTotalAmount('mollie_payment_fee'));
        $this->assertEquals(0, $total->getBaseTotalAmount('mollie_payment_fee'));

        if ($extensionAttributes) {
            $this->assertEquals(0, $extensionAttributes->getMolliePaymentFee());
            $this->assertEquals(0, $extensionAttributes->getBaseMolliePaymentFee());
        }

        $instance->collect($quote, $shippingAssignment, $total);

        $this->assertEquals(1.61, $total->getTotalAmount('mollie_payment_fee'));
        $this->assertEquals(1.61, $total->getBaseTotalAmount('mollie_payment_fee'));

        if ($extensionAttributes) {
            $this->assertEquals(1.61, $extensionAttributes->getMolliePaymentFee());
            $this->assertEquals(1.61, $extensionAttributes->getBaseMolliePaymentFee());
        }
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
