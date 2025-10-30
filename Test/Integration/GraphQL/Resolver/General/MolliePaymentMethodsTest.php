<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\General;

use Exception;
use Magento\PageCache\Model\Cache\Type;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetEnabledMethodsRequest;
use Mollie\Api\Http\Requests\GetPaginatedTerminalsRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Method;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\GraphQLTestCase;
use stdClass;
use Zend_Cache;

/**
 * @magentoAppArea graphql
 */
class MolliePaymentMethodsTest extends GraphQLTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/title custom_title
     * @return void
     */
    public function testReturnsMethodsWithTheCorrectTitle(): void
    {
        $result = $this->callEndpoint();

        $found = false;
        foreach ($result as $method) {
            if ($method['code'] == 'ideal') {
                $this->assertEquals('ideal', $method['code']);
                $this->assertEquals('custom_title', $method['name']);
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception('The result didn\'t include iDeal');
        }
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/active 0
     * @return void
     */
    public function testDoesNotReturnMethodWhenNotActive(): void
    {
        $result = $this->callEndpoint();

        $this->assertCount(1, $result);
        $this->assertEquals('ideal', $result[0]['code']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/title CREDITCARD
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/title iDeal
     * @return void
     */
    public function testSortsMethodsByName(): void
    {
        $this->cleanCache();

        $result = $this->callEndpoint();

        $this->assertCount(2, $result);
        $this->assertEquals('CREDITCARD', $result[0]['name']);
        $this->assertEquals('iDeal', $result[1]['name']);
    }

    private function callEndpoint(): array
    {
        $this->loadFakeEncryptor()->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');

        $client = MollieApiClient::fake([
            GetEnabledMethodsRequest::class => MockResponse::ok('method-list'),
            GetPaginatedTerminalsRequest::class => MockResponse::ok('terminal-list'),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        return $this->graphQlQuery('
            query {
                molliePaymentMethods {
                    methods {
                        code
                        name
                    }
                }
            }
        ')['molliePaymentMethods']['methods'];
    }

    private function arrayToObject(array $array, bool $nested = false): stdClass|Method
    {
        $client = $this->objectManager->get(MollieApiClient::class);
        $method = new Method($client);
        $method->status = 'activated';

        $object = $nested ? new stdClass() : $method;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->arrayToObject($value, true);
            }

            $object->$key = $value;
        }

        return $object;
    }

    /**
     * @return void
     */
    public function cleanCache(): void
    {
        /** @var Type::class $cache */
        $cache = $this->objectManager->get(Type::class);
        $cache->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            ['mollie_payment_methods'],
        );
    }
}
