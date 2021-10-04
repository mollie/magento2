<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Checkout;

use Mollie\Payment\Test\Integration\GraphQLTestCase;

class ProcessTransactionTest extends GraphQLTestCase
{
    /**
     * @throws \Exception
     * @magentoAppArea graphql
     */
    public function testThrowsANotFoundExceptionWhenTokenDoesNotExists()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: No order found with token "non-existing-payment-token"');

        $client = $this->graphQlQuery('mutation {
          mollieProcessTransaction(input: {
              payment_token: "non-existing-payment-token"
          }) {
            paymentStatus
          }
        }');
    }
}
