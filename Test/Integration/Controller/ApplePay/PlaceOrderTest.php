<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\ApplePay;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Address;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Validator\Factory as ValidatorFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\TestCase\AbstractController;
use PHPUnit\Framework\Attributes\DataProvider;

class PlaceOrderTest extends AbstractController
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_general/enable_second_chance_email 0
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @dataProvider localityProvider
     */
    #[DataProvider('localityProvider')]
    public function testPlacesTheOrderWithACityTheMagentoValidatorAccepts(string $locality): void
    {
        $this->prepareGuestQuote();

        $this->dispatchPlaceOrder($locality);

        $response = json_decode($this->getResponse()->getBody(), true);
        $this->assertSame(
            200,
            $this->getResponse()->getHttpResponseCode(),
            'Placing the Apple Pay order failed for the locality "' . $locality . '": ' . ($response['message'] ?? ''),
        );
        $this->assertFalse($response['error']);

        $city = $this->getPlacedOrder()->getBillingAddress()->getCity();
        $this->assertTrue(
            $this->isAcceptedByMagento($city),
            'The city "' . $city . '" is rejected by the Magento address validator',
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_general/enable_second_chance_email 0
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @dataProvider localityProvider
     */
    #[DataProvider('localityProvider')]
    public function testKeepsTheLocalityUnchangedWhenTheMagentoValidatorAcceptsIt(string $locality): void
    {
        if (!$this->isAcceptedByMagento($locality)) {
            $this->markTestSkipped('The Magento address validator of this version rejects "' . $locality . '"');
        }

        $this->prepareGuestQuote();

        $this->dispatchPlaceOrder($locality);

        $this->assertSame($locality, $this->getPlacedOrder()->getBillingAddress()->getCity());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test test_dummydummydummydummydummydummy
     * @magentoConfigFixture current_store payment/mollie_general/enable_second_chance_email 0
     * @magentoConfigFixture current_store payment/mollie_methods_applepay/active 1
     * @dataProvider supportedCityProvider
     */
    #[DataProvider('supportedCityProvider')]
    public function testFallsBackToASupportedCityWhenTheMagentoValidatorRejectsTheLocality(
        string $locality,
        string $supportedCity,
    ): void {
        if ($this->isAcceptedByMagento($locality)) {
            $this->markTestSkipped('The Magento address validator of this version accepts "' . $locality . '"');
        }

        $this->prepareGuestQuote();

        $this->dispatchPlaceOrder($locality);

        $this->assertSame($supportedCity, $this->getPlacedOrder()->getBillingAddress()->getCity());
    }

    public static function localityProvider(): array
    {
        return array_map(
            static fn (array $case): array => [$case[0]],
            self::supportedCityProvider(),
        );
    }

    public static function supportedCityProvider(): array
    {
        return [
            'period' => ['St. Albans', 'St Albans'],
            'digits' => ['Rome 00184', 'Rome'],
            'parentheses' => ['Saint-Denis (93)', 'Saint-Denis'],
            'slash' => ['Newcastle / Tyne', 'Newcastle Tyne'],
            'typographic apostrophe' => ['O’Fallon', "O'Fallon"],
        ];
    }

    private function prepareGuestQuote(): void
    {
        /** @var Quote $quote */
        $quote = $this->_objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $quote->setIsMultiShipping(false);
        $quote->setIsActive(true);
        $quote->getShippingAddress()->setCollectShippingRates(true);

        $this->_objectManager->get(CartRepositoryInterface::class)->save($quote);
        $this->_objectManager->get(Session::class)->setQuoteId($quote->getId());
    }

    private function dispatchPlaceOrder(string $locality): void
    {
        $contact = [
            'addressLines' => ['Sample Street 1'],
            'countryCode' => 'GB',
            'givenName' => 'Sample',
            'familyName' => 'Shopper',
            'locality' => $locality,
            'postalCode' => 'AL1 1AA',
            'phoneNumber' => '0123456789',
            'emailAddress' => 'sample.shopper@example.com',
        ];

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'shippingAddress' => $contact,
            'billingAddress' => $contact,
            'shippingMethod' => ['identifier' => 'flatrate__SPLIT__flatrate'],
            'applePayPaymentToken' => '{"paymentData":"sample"}',
        ]);

        $this->dispatch('mollie/applePay/placeOrder');
    }

    private function getPlacedOrder(): OrderInterface
    {
        /** @var Order $order */
        $order = $this->_objectManager->create(Order::class);
        $order->loadByIncrementId('test01');

        $this->assertNotNull($order->getId(), 'No order was created for the Apple Pay request');

        return $order;
    }

    private function isAcceptedByMagento(string $city): bool
    {
        /** @var Address $address */
        $address = $this->_objectManager->create(Address::class);
        $address->setFirstname('Sample')
            ->setLastname('Shopper')
            ->setStreet(['Sample Street 1'])
            ->setCity($city)
            ->setPostcode('AL1 1AA')
            ->setTelephone('0123456789')
            ->setCountryId('GB');

        return $this->_objectManager->get(ValidatorFactory::class)
            ->createValidator('customer_address', 'save')
            ->isValid($address);
    }
}
