<?php

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\General;

use Mollie\Payment\Test\Integration\GraphQLTestCase;

/**
 * @magentoAppArea graphql
 */
class MollieStoreConfigTest extends GraphQLTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/profileid pfl_testvalue
     * @return void
     */
    public function testFetchesTheProfileId(): void
    {
        $result = $this->graphQlQuery('
            query {
                storeConfig {
                    mollie {
                        profile_id
                    }
                }
            }
        ');

        $this->assertEquals('pfl_testvalue', $result['storeConfig']['mollie']['profile_id']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/type live
     * @return void
     */
    public function testFetchesTheLiveMode(): void
    {
        $result = $this->graphQlQuery('
            query {
                storeConfig {
                    mollie {
                        live_mode
                    }
                }
            }
        ');

        $this->assertEquals(true, $result['storeConfig']['mollie']['live_mode']);
    }
}
