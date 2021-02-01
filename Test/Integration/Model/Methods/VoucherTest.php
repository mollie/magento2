<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Model\Methods\Voucher;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class VoucherTest extends IntegrationTestCase
{
    protected $instance = Voucher::class;

    protected $code = 'voucher';

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_voucher/category null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testIsNotAvailableWhenTheCategoryIsNotSet()
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(CartInterface::class);

        /** @var Voucher $instance */
        $instance = $this->objectManager->create(Voucher::class);
        $this->assertFalse($instance->isAvailable($cart));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_methods_voucher/category meal
     * @magentoConfigFixture default_store payment/mollie_methods_voucher/active 1
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testIsAvailableWhenTheCategoryIsSet()
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(CartInterface::class);

        /** @var Voucher $instance */
        $instance = $this->objectManager->create(Voucher::class);
        $this->assertTrue($instance->isAvailable($cart));
    }
}
