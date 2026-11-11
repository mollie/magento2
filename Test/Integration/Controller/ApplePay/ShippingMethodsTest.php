<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\ApplePay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Manager\Proxy as EventManagerProxy;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ShippingMethodManagement;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Test\Fakes\Framework\Event\EventManagerFake;
use Mollie\Payment\Test\Fakes\Logger\MollieLoggerFake;
use Mollie\Payment\Test\Fakes\Quote\ShippingMethodManagementFake;
use Mollie\Payment\Test\Integration\ControllerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ShippingMethodsTest extends ControllerTestCase
{
    private const SIMPLE_QUOTE = 'test01';
    private const VIRTUAL_QUOTE = 'test_order_with_virtual_product_without_address';

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @dataProvider unusableCountryProvider
     */
    #[DataProvider('unusableCountryProvider')]
    public function testReturnsAnInvalidShippingContactWhenTheCountryIsUnusable(string $countryCode): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethods($countryCode, '1012AB');

        $this->assertInvalidShippingContact($response);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     */
    public function testReturnsAnInvalidShippingContactWhenARequiredPostalCodeIsMissing(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethods('NL', '');

        $this->assertInvalidShippingContact($response);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store general/country/allow NL
     */
    public function testReturnsAnInvalidShippingContactWhenTheCountryIsNotAllowed(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethods('DE', '10115');

        $this->assertInvalidShippingContact($response);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 0
     */
    public function testReturnsAnInvalidShippingContactWhenNoShippingMethodIsAvailable(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethods('NL', '1012AB');

        $this->assertInvalidShippingContact($response);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     */
    public function testLogsAndReturnsAnInvalidShippingContactWhenTheShippingMethodsCannotBeRetrieved(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);
        $shippingMethodManagement = (new ShippingMethodManagementFake())->givenGetListFailsWith(
            new StateException(__('The shipping address is missing. Set the address and try again.')),
        );
        $this->_objectManager->addSharedInstance($shippingMethodManagement, ShippingMethodManagementInterface::class);
        $this->_objectManager->addSharedInstance($shippingMethodManagement, ShippingMethodManagement::class);
        $logger = $this->_objectManager->create(MollieLoggerFake::class);
        $this->_objectManager->addSharedInstance($logger, MollieLogger::class);

        $response = $this->dispatchShippingMethods('NL', '1012AB');

        $this->assertInvalidShippingContact($response);
        $this->assertCount(1, $logger->getErrorLogs());
        $this->assertSame(
            'The shipping address is missing. Set the address and try again.',
            $logger->getErrorLogs()[0]['data']['exception'],
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     */
    public function testReturnsTheShippingMethodsForAValidShippingContact(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethods('NL', '1012AB');

        $this->assertValidShippingContact($response);
        $this->assertSame('flatrate__SPLIT__flatrate', $response['shipping_methods'][0]['identifier']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     */
    public function testReturnsTheShippingMethodsWithoutAPostalCodeWhenTheCountryDoesNotRequireOne(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethods('HK', '');

        $this->assertValidShippingContact($response);
        $this->assertNotEmpty($response['shipping_methods']);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_virtual_product_saved.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     */
    public function testAcceptsAVirtualCartThatHasNoShippingMethods(): void
    {
        $this->useQuote(self::VIRTUAL_QUOTE);

        $response = $this->dispatchShippingMethods('NL', '1012AB');

        $this->assertValidShippingContact($response);
        $this->assertSame([], $response['shipping_methods']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @dataProvider missingCountryProvider
     */
    #[DataProvider('missingCountryProvider')]
    public function testReturnsAJsonErrorToALegacyFrontendWhenTheCountryIsMissing(string $countryCode): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethodsFromALegacyFrontend($countryCode, '1012AB');

        $this->assertSame(400, $this->getResponse()->getHttpResponseCode());
        $this->assertArrayNotHasKey('invalid_shipping_contact', $response);
        $this->assertSame('Please check your shipping address and try again.', $response['message']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 0
     */
    public function testReturnsAnEmptyShippingMethodListToALegacyFrontendWhenNoShippingMethodIsAvailable(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethodsFromALegacyFrontend('NL', '1012AB');

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertValidShippingContact($response);
        $this->assertSame([], $response['shipping_methods']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     */
    public function testReturnsTheShippingMethodsToALegacyFrontendForAValidShippingContact(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethodsFromALegacyFrontend('NL', '1012AB');

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertValidShippingContact($response);
        $this->assertSame('flatrate__SPLIT__flatrate', $response['shipping_methods'][0]['identifier']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store general/country/allow NL
     */
    public function testReturnsTheShippingMethodsToALegacyFrontendForACountryThatIsNotAllowed(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethodsFromALegacyFrontend('DE', '10115');

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertValidShippingContact($response);
        $this->assertNotEmpty($response['shipping_methods']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     */
    public function testReturnsTheShippingMethodsToALegacyFrontendWithoutARequiredPostalCode(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);

        $response = $this->dispatchShippingMethodsFromALegacyFrontend('NL', '');

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertValidShippingContact($response);
        $this->assertNotEmpty($response['shipping_methods']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture current_store carriers/flatrate/active 0
     */
    public function testOffersTheShippingMethodAnObserverAddsWhenMagentoHasNone(): void
    {
        $this->useQuote(self::SIMPLE_QUOTE);
        $pickupMethod = ['identifier' => 'pickup__SPLIT__store', 'label' => 'Pickup', 'amount' => '0.00', 'detail' => ''];
        $eventManager = (new EventManagerFake($this->_objectManager->get(ManagerInterface::class)))
            ->givenObserverFor('mollie_applepay_direct_shippingmethods', function (array $data) use ($pickupMethod): void {
                /** @var DataObject $transport */
                $transport = $data['transport'];
                $transport->setData('shipping_methods', [$pickupMethod]);
            });
        $this->_objectManager->addSharedInstance($eventManager, ManagerInterface::class);
        $this->_objectManager->addSharedInstance($eventManager, EventManagerProxy::class);

        $response = $this->dispatchShippingMethods('NL', '1012AB');

        $this->assertValidShippingContact($response);
        $this->assertSame([$pickupMethod], $response['shipping_methods']);
    }

    public static function missingCountryProvider(): array
    {
        return [
            'empty' => [''],
            'undefined sent by the frontend' => ['undefined'],
        ];
    }

    public static function unusableCountryProvider(): array
    {
        return [
            'empty' => [''],
            'undefined sent by the frontend' => ['undefined'],
            'unknown country' => ['XX'],
        ];
    }

    private function useQuote(string $reservedOrderId): void
    {
        /** @var Quote $quote */
        $quote = $this->_objectManager->create(Quote::class);
        $quote->load($reservedOrderId, 'reserved_order_id');
        $quote->setIsMultiShipping(false);
        $quote->setIsActive(true);
        $quote->getShippingAddress()->setCollectShippingRates(true);

        $this->_objectManager->get(CartRepositoryInterface::class)->save($quote);
        $this->_objectManager->get(Session::class)->setQuoteId($quote->getId());
    }

    private function dispatchShippingMethods(string $countryCode, string $postalCode): array
    {
        $this->dispatchWithPostValues([
            'countryCode' => $countryCode,
            'postalCode' => $postalCode,
            'supportsInvalidShippingContact' => '1',
        ]);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());

        return json_decode($this->getResponse()->getBody(), true);
    }

    private function dispatchShippingMethodsFromALegacyFrontend(string $countryCode, string $postalCode): array
    {
        $this->dispatchWithPostValues([
            'countryCode' => $countryCode,
            'postalCode' => $postalCode,
        ]);

        return json_decode($this->getResponse()->getBody(), true);
    }

    private function dispatchWithPostValues(array $postValues): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($postValues);

        $this->dispatch('mollie/applePay/shippingMethods');
    }

    private function assertInvalidShippingContact(array $response): void
    {
        $this->assertTrue($response['invalid_shipping_contact'] ?? false);
        $this->assertSame([], $response['shipping_methods']);
        $this->assertSame('Please check your shipping address and try again.', $response['message']);
    }

    private function assertValidShippingContact(array $response): void
    {
        $this->assertArrayNotHasKey('invalid_shipping_contact', $response);
        $this->assertNotEmpty($response['totals']);
    }
}
