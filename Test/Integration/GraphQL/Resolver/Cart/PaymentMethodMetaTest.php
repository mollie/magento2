<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Cart;

use GraphQL\Type\Definition\FieldDefinition;
use Magento\Framework\App\ProductMetadataInterface;
use Mollie\Payment\GraphQL\Resolver\Cart\PaymentMethodMeta;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentMethodMetaTest extends IntegrationTestCase
{
    /**
     * @magentoAppArea graphql
     */
    public function testReturnsAnEmptyResponseForNonMollieMethods()
    {
        $instance = $this->objectManager->create(PaymentMethodMeta::class);

        $result = $this->callResolve($instance, ['code' => 'checkmo']);

        $this->assertNull($result['image']);
    }

    public function testReturnsTheImageForMollieMethods()
    {
        $instance = $this->objectManager->create(PaymentMethodMeta::class);

        $result = $this->callResolve($instance, ['code' => 'mollie_methods_ideal']);

        $this->assertStringContainsString('Mollie_Payment/images/methods/ideal.svg', $result['image']);
    }

    public function testTheImagesIsAFrontendPath()
    {
        $instance = $this->objectManager->create(PaymentMethodMeta::class);

        $result = $this->callResolve($instance, ['code' => 'mollie_methods_ideal']);

        $this->assertStringContainsString('frontend/Magento/luma', $result['image']);
    }

    public function callResolve(PaymentMethodMeta $instance, $value = null, $args = null)
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
                'fieldDefinition' => FieldDefinition::create([
                    'name' => 'test',
                    'type' => $this->objectManager->create(\Magento\Framework\GraphQl\Schema\Type\BooleanType::class),
                ]),
                'values' => [],
                'fieldName' => 'testfield',
                'fieldNodes' => [],
                'returnType' => 'string',
                'parentType' => new \GraphQL\Type\Definition\ObjectType(['name' => 'testfield']),
                'path' => [],
                'schema' => $this->objectManager->create(\GraphQL\Type\Schema::class, ['config' => []]),
                'fragments' => [],
                'rootValue' => '',
                'operation' => $this->objectManager->create(\GraphQL\Language\AST\OperationDefinitionNode::class, [
                    'vars' => [
                        'operation' => 'query',
                    ]
                ]),
                'variableValues' => [],
            ]),
            $value,
            $args
        );
    }
}
