<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\ApplePay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\TestCase\AbstractController;
use PHPUnit\Framework\Attributes\DataProvider;

class ShippingMethodsTest extends AbstractController
{
    private const FLATRATE_IDENTIFIER = 'flatrate__SPLIT__flatrate';

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     */
    public function testSelectsTheShippingMethodTheCustomerPicked(): void
    {
        $this->prepareGuestQuote();

        $this->dispatchShippingMethods(['identifier' => self::FLATRATE_IDENTIFIER]);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertSame('flatrate_flatrate', $this->getActiveQuote()->getShippingAddress()->getShippingMethod());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @dataProvider unusableShippingMethodProvider
     */
    #[DataProvider('unusableShippingMethodProvider')]
    public function testReturnsTheAvailableShippingMethodsWhenTheShippingMethodIsUnusable(
        array|string $shippingMethod,
    ): void {
        $this->prepareGuestQuote();

        $this->dispatchShippingMethods($shippingMethod);

        $response = json_decode($this->getResponse()->getBody(), true);
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertContains(
            self::FLATRATE_IDENTIFIER,
            array_column($response['shipping_methods'], 'identifier'),
        );
    }

    public static function unusableShippingMethodProvider(): array
    {
        return [
            'the string "undefined" sent by the Hyvä Apple Pay buttons' => ['undefined'],
            'any other plain string' => [self::FLATRATE_IDENTIFIER],
            'an array without an identifier' => [['label' => 'Flat Rate']],
            'an empty identifier' => [['identifier' => '']],
        ];
    }

    private function prepareGuestQuote(): void
    {
        /** @var Quote $quote */
        $quote = $this->_objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $quote->setIsMultiShipping(false);
        $quote->setIsActive(true);

        $this->_objectManager->get(CartRepositoryInterface::class)->save($quote);
        $this->_objectManager->get(Session::class)->setQuoteId($quote->getId());
    }

    private function dispatchShippingMethods(array|string $shippingMethod): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'countryCode' => 'gb',
            'postalCode' => 'AL1 1AA',
            'shippingMethod' => $shippingMethod,
        ]);

        $this->dispatch('mollie/applePay/shippingMethods');
    }

    private function getActiveQuote(): Quote
    {
        /** @var Quote $quote */
        $quote = $this->_objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        return $quote;
    }
}
