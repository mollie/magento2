<?php

namespace Mollie\Payment\Model\Adminhtml\Backend;

use Magento\Framework\Exception\ValidatorException;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class VerifiyPaymentFeeTest extends IntegrationTestCase
{
    public function testReplacesCommasWithADot()
    {
        /** @var VerifiyPaymentFee $instance */
        $instance = $this->objectManager->create(VerifiyPaymentFee::class);

        $instance->setValue('1,23');

        $instance->beforeSave();

        $this->assertSame('1.23', $instance->getValue());
    }

    public function testThrowsAnExceptionWhenTheAmountIsTooHigh()
    {
        /** @var VerifiyPaymentFee $instance */
        $instance = $this->objectManager->create(VerifiyPaymentFee::class);

        $instance->setValue(VerifiyPaymentFee::MAXIMUM_PAYMENT_FEE_AMOUNT + 0.01);

        try {
            $instance->beforeSave();
        } catch (ValidatorException $exception) {
            $this->assertInstanceOf(ValidatorException::class, $exception);
            $this->assertContains((string)VerifiyPaymentFee::MAXIMUM_PAYMENT_FEE_AMOUNT, $exception->getMessage());
            return;
        }

        $this->fail('We expected an ' . ValidatorException::class . ' but got none');
    }
}
