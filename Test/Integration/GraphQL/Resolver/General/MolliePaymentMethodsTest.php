<?php

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\General;

use Magento\PageCache\Model\Cache\Type;
use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\GraphQLTestCase;

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
        $result = $this->callEndpoint([
            $this->arrayToObject([
                'id' => 'ideal',
                'description' => 'iDeal',
                'image' => ['svg' => 'ideal.svg'],
            ])
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('ideal', $result[0]['code']);
        $this->assertEquals('custom_title', $result[0]['name']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_eps/active 0
     * @return void
     */
    public function testDoesNotReturnMethodWhenNotActive(): void
    {
        $result = $this->callEndpoint([
            $this->arrayToObject([
                'id' => 'ideal',
                'description' => 'iDeal',
                'image' => ['svg' => 'ideal.svg'],
            ]),
            $this->arrayToObject([
                'id' => 'eps',
                'description' => 'EPS',
                'image' => ['svg' => 'eps.svg'],
            ])
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('ideal', $result[0]['code']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_eps/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_eps/title EPS
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/title iDeal
     * @magentoConfigFixture default_store payment/mollie_methods_kbc/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_kbc/title KBC/CBC
     * @return void
     */
    public function testSortsMethodsByName(): void
    {
        $this->cleanCache();

        $result = $this->callEndpoint([
            $this->arrayToObject([
                'id' => 'ideal',
                'description' => 'iDeal',
                'image' => ['svg' => 'ideal.svg'],
            ]),
            $this->arrayToObject([
                'id' => 'eps',
                'description' => 'EPS',
                'image' => ['svg' => 'eps.svg'],
            ]),
            $this->arrayToObject([
                'id' => 'kbc',
                'description' => 'KBC/CBC',
                'image' => ['svg' => 'kbc.svg'],
            ])
        ]);

        $this->assertCount(3, $result);
        $this->assertEquals('EPS', $result[0]['name']);
        $this->assertEquals('iDeal', $result[1]['name']);
        $this->assertEquals('KBC/CBC', $result[2]['name']);
    }

    /**
     * @magentoCache all enabled
     * @return void
     */
    public function testCachesTheOutcome(): void
    {
        if (getenv('CI')) {
            /**
             * When enabled in CI we receive this message:
             *
             * ERROR: deployment configuration is corrupted. The application state is no longer valid.
             * Further tests may fail. This test failure may be misleading, if you are re-running it on a
             * corrupted application.
             */
            $this->markTestSkipped('This test fails on CI for unknown reasons');
        }

        $this->cleanCache();

        // This result is saved to the cache
        $result = $this->callEndpoint([
            $this->arrayToObject([
                'id' => 'ideal',
                'description' => 'iDeal',
                'image' => ['svg' => 'ideal.svg'],
            ]),
        ]);

        $this->assertCount(1, $result);

        // If the cache is working correctly, the endpoint is never called, and we won't get 3 methods back but only 1.
        $result = $this->callEndpoint([
            $this->arrayToObject([
                'id' => 'ideal',
                'description' => 'iDeal',
                'image' => ['svg' => 'ideal.svg'],
            ]),
            $this->arrayToObject([
                'id' => 'eps',
                'description' => 'EPS',
                'image' => ['svg' => 'eps.svg'],
            ]),
            $this->arrayToObject([
                'id' => 'kbc',
                'description' => 'KBC/CBC',
                'image' => ['svg' => 'kbc.svg'],
            ])
        ]);

        $this->assertCount(1, $result);
    }

    private function callEndpoint($methods): array
    {
        $this->loadFakeEncryptor()->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');

        $methodsEndpointMock = $this->createMock(MethodEndpoint::class);
        $methodsEndpointMock->method('allActive')->willReturn($methods);

        $mollieApiMock = $this->createMock(MollieApiClient::class);
        $mollieApiMock->methods = $methodsEndpointMock;

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($mollieApiMock);
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

    private function arrayToObject($array): \stdClass
    {
        $object = new \stdClass();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->arrayToObject($value);
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
        /** @var \Magento\PageCache\Model\Cache\Type::class $cache */
        $cache = $this->objectManager->get(Type::class);
        $cache->clean(
            \Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            ['mollie_payment_methods']
        );
    }
}
