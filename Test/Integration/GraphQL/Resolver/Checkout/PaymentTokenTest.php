<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Checkout;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\GraphQL\Resolver\Checkout\PaymentToken;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentTokenTest extends IntegrationTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $version = $this->objectManager->get(ProductMetadataInterface::class)->getVersion();
        if (version_compare($version, '2.3', '<=')) {
            $this->markTestSkipped('This test only works on Magento 2.3 and higher.');
        }
    }

    public function testDoesNothingWhenTheOrderDoesNotExists()
    {
        /** @var PaymentToken $instance */
        $instance = $this->objectManager->create(PaymentToken::class);

        $result = $this->callResolve($instance, ['order_id' => 123]);

        $this->assertNull($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsTheExistingToken()
    {
        $order = $this->loadOrder('100000001');

        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        /** @var PaymentTokenInterface $tokenModel */
        $tokenModel = $this->objectManager->create(PaymentTokenInterface::class);
        $tokenModel->setToken('randomstring');
        $tokenModel->setOrderId($order->getId());
        $tokenModel->setCartId($quote->getId());
        $this->objectManager->get(PaymentTokenRepositoryInterface::class)->save($tokenModel);

        /** @var PaymentToken $instance */
        $instance = $this->objectManager->create(PaymentToken::class);

        $result = $this->callResolve($instance, ['order_id' => '100000001']);

        $this->assertEquals('randomstring', $result);
    }

    public function callResolve(PaymentToken $instance, $value = null, $args = null)
    {
        return $instance->resolve(
            $this->objectManager->create(\Magento\Framework\GraphQl\Config\Element\Field::class, [
                'name' => 'testfield',
                'type' => 'string',
                'required' => false,
                'isList' => false,
            ]),
            $this->objectManager->create(\Magento\Framework\GraphQl\Query\Resolver\ContextInterface::class),
            $this->objectManager->create(\Magento\Framework\GraphQl\Schema\Type\ResolveInfo::class, [
                'fieldName' => 'testfield',
                'fieldNodes' => [],
                'returnType' => 'string',
                'parentType' => new \GraphQL\Type\Definition\ObjectType(['name' => 'testfield']),
                'path' => [],
                'schema' => $this->objectManager->create(\GraphQL\Type\Schema::class, ['config' => []]),
                'fragments' => [],
                'rootValue' => '',
                'operation' => null,
                'variableValues' => [],
            ]),
            $value,
            $args
        );
    }
}
