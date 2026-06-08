<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Plugin\Quote\Api;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Plugin\Quote\Api\LimitMethodsForRecurringPayments;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LimitMethodsForRecurringPaymentsTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product_and_custom_option.php
     */
    public function testLimitsListWhenTheCartHasARecurringProduct(): void
    {
        /** @var Quote $cart */
        $cart = $this->objectManager->create(Session::class)->getQuote();

        $items = $cart->getItems();
        $item = array_shift($items);

        $serializer = $this->objectManager->get(SerializerInterface::class);
        $buyRequestJson = $item->getOptionByCode('info_buyRequest')->getValue();
        $buyRequest = $serializer->unserialize($buyRequestJson);
        $buyRequest['mollie_metadata'] = [
            'is_recurring' => true,
        ];
        $item->addOption(['code' => 'info_buyRequest', 'value' => $serializer->serialize($buyRequest)]);

        /** @var LimitMethodsForRecurringPayments $instance */
        $instance = $this->objectManager->create(LimitMethodsForRecurringPayments::class);

        $methods = [
            $this->objectManager->create(Ideal::class),
            $this->objectManager->create(ApplePay::class),
        ];

        $result = $instance->afterGetList(
            $this->objectManager->create(PaymentMethodManagementInterface::class),
            $methods,
            $cart->getId(),
        );

        $this->assertCount(1, $result);
        $this->assertEquals('mollie_methods_ideal', $result[0]->getCode());
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product_and_custom_option.php
     */
    public function testDoesNotLimitsTheListWhenNotARecurringCart(): void
    {
        /** @var Quote $cart */
        $cart = $this->objectManager->create(Session::class)->getQuote();

        /** @var LimitMethodsForRecurringPayments $instance */
        $instance = $this->objectManager->create(LimitMethodsForRecurringPayments::class);

        $methods = [
            $this->objectManager->create(Ideal::class),
            $this->objectManager->create(ApplePay::class),
        ];

        $result = $instance->afterGetList(
            $this->objectManager->create(PaymentMethodManagementInterface::class),
            $methods,
            $cart->getId(),
        );

        $this->assertCount(2, $result);
        $this->assertEquals('mollie_methods_ideal', $result[0]->getCode());
        $this->assertEquals('mollie_methods_applepay', $result[1]->getCode());
    }
}
