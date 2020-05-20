<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Ingegration\Model\Methods;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Model\Methods\Mealvoucher;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MealvoucherTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_methods_mealvoucher/category null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testIsNotAvailableWhenTheCategoryIsNotSet()
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(CartInterface::class);

        /** @var Mealvoucher $instance */
        $instance = $this->objectManager->create(Mealvoucher::class);
        $this->assertFalse($instance->isAvailable($cart));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_methods_mealvoucher/category food_and_drinks
     * @magentoConfigFixture default_store payment/mollie_general/enabled true
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testIsAvailableWhenTheCategoryIsSet()
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(CartInterface::class);

        /** @var Mealvoucher $instance */
        $instance = $this->objectManager->create(Mealvoucher::class);
        $this->assertTrue($instance->isAvailable($cart));
    }
}