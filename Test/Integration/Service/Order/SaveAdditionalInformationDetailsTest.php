<?php

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mollie\Payment\Service\Order\SaveAdditionalInformationDetails;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SaveAdditionalInformationDetailsTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/encrypt_payment_details 0
     * @return void
     */
    public function testDoesNotEncryptTheDataWhenDisabled(): void
    {
        /** @var SaveAdditionalInformationDetails $instance */
        $instance = $this->objectManager->create(SaveAdditionalInformationDetails::class);

        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $this->assertNull($payment->getAdditionalInformation('details'));

        $instance->execute($payment, (object) ['foo' => 'bar']);

        $this->assertEquals('{"foo":"bar"}', $payment->getAdditionalInformation('details'));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/encrypt_payment_details 1
     * @return void
     */
    public function testDoesEncryptTheDataWhenEnabled(): void
    {
        /** @var SaveAdditionalInformationDetails $instance */
        $instance = $this->objectManager->create(SaveAdditionalInformationDetails::class);

        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $this->assertNull($payment->getAdditionalInformation('details'));

        $instance->execute($payment, (object) ['foo' => 'bar']);

        /** @var EncryptorInterface $encryptor */
        $encryptor = $this->objectManager->get(EncryptorInterface::class);

        $this->assertEquals(
            '{"foo":"bar"}',
            $encryptor->decrypt($payment->getAdditionalInformation('details'))
        );
    }
}
