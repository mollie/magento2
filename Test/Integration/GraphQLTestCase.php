<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration;

use Exception;
use Magento\Framework\GraphQl\Query\Fields as QueryFields;
use Magento\Framework\Module\Manager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GraphQl\Controller\GraphQl;
use Magento\GraphQl\Service\GraphQlRequest;

class GraphQLTestCase extends IntegrationTestCase
{
    /**
     * @var SerializerInterface
     */
    protected $json;

    /**
     * @var GraphQlRequest
     */
    protected $graphQlRequest;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Manager $moduleManager */
        $moduleManager = $this->objectManager->get(Manager::class);
        if (!$moduleManager->isEnabled('Magento_GraphQl')) {
            $this->markTestSkipped('Module Magento_GraphQl is not enabled');
        }

        $this->json = $this->objectManager->get(SerializerInterface::class);
        $this->graphQlRequest = $this->objectManager->create(GraphQlRequest::class);
    }

    /**
     * @param $query
     * @return mixed
     * @throws Exception
     */
    protected function graphQlQuery($query)
    {
        $this->resetGraphQlCache();
        $response = $this->graphQlRequest->send($query);
        $responseData = $this->json->unserialize($response->getContent());

        if (isset($responseData['errors'])) {
            $this->processErrors($responseData);
        }

        return $responseData['data'];
    }

    /**
     * @param $body
     * @throws Exception
     */
    private function processErrors(array $body): void
    {
        $errorMessage = '';
        foreach ($body['errors'] as $error) {
            if (!isset($error['message'])) {
                continue;
            }

            $errorMessage .= $error['message'] . PHP_EOL;
            if (isset($error['debugMessage'])) {
                $errorMessage .= $error['debugMessage'] . PHP_EOL;
            }
        }

        throw new Exception('GraphQL response contains errors: ' . $errorMessage);
    }

    private function resetGraphQlCache(): void
    {
        $this->objectManager->removeSharedInstance(GraphQl::class);
        $this->objectManager->removeSharedInstance(QueryFields::class);
        $this->graphQlRequest = $this->objectManager->create(GraphQlRequest::class);
    }

    protected function prepareCustomerCart(?string $paymentMethod = 'mollie_methods_ideal'): string
    {
        $cartId = $this->graphQlQuery(
            'mutation {createEmptyCart}',
        )['createEmptyCart'];

        $this->graphQlQuery('
            mutation {
                setGuestEmailOnCart(
                    input: {
                        cart_id: "' . $cartId . '"
                        email: "example@mollie.com"
                    }
                ) {
                    cart {
                        email
                    }
                }
            }
        ');

        $this->graphQlQuery('
            mutation {
                addSimpleProductsToCart(
                    input: {
                        cart_id: "' . $cartId . '"
                        cart_items: [
                            {
                                data: {
                                    quantity: 1
                                    sku: "simple"
                                }
                            }
                        ]
                    }
                ) {
                    cart {
                        items {
                            id
                        }
                    }
                }
            }
        ');

        $this->graphQlQuery('
            mutation {
                setBillingAddressOnCart(
                    input: {
                        cart_id: "' . $cartId . '"
                        billing_address: {
                            address: {
                                firstname: "John"
                                lastname: "Doe"
                                company: "Acme"
                                street: ["Main St", "123"]
                                city: "Anytown"
                                postcode: "1234AB"
                                country_code: "NL"
                                telephone: "123-456-0000"
                                save_in_address_book: false
                            }
                            use_for_shipping: true
                        }
                    }
                ) {
                    cart {
                        billing_address {
                            firstname
                            lastname
                            company
                            street
                            city
                            postcode
                            telephone
                            country {
                                code
                                label
                            }
                        }
                        shipping_addresses {
                            firstname
                            lastname
                            company
                            street
                            city
                            postcode
                            telephone
                            country {
                                code
                                label
                            }
                        }
                    }
                }
            }
        ');

        $token = $this->graphQlQuery('
            query {
                cart(cart_id: "' . $cartId . '") {
                    shipping_addresses {
                        available_shipping_methods {
                            error_message
                            method_code
                            method_title
                        }
                    }
                }
            }
        ');

        $method = $token['cart']['shipping_addresses'][0]['available_shipping_methods'][0];

        $this->graphQlQuery('
            mutation {
                setShippingMethodsOnCart(input: {
                    cart_id: "' . $cartId . '"
                    shipping_methods: [
                        {
                            carrier_code: "' . $method['method_code'] . '"
                            method_code: "' . $method['method_code'] . '"
                        }
                    ]
                }) {
                    cart {
                        shipping_addresses {
                            selected_shipping_method {
                                carrier_code
                                method_code
                                carrier_title
                                method_title
                            }
                        }
                    }
                }
            }
        ');

        if ($paymentMethod) {
            $this->graphQlQuery('
                mutation {
                    setPaymentMethodOnCart(input: {
                        cart_id: "' . $cartId . '"
                        payment_method: {
                            code: "' . $paymentMethod . '"
                        }
                    }) {
                        cart {
                            selected_payment_method {
                                code
                            }
                        }
                    }
                }
            ');
        }

        return $cartId;
    }

    protected function prepareCustomerCartWithoutPayment(): string
    {
        return $this->prepareCustomerCart(null);
    }
}
