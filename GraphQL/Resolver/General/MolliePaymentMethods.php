<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\General;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Mollie\Api\Resources\Method;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\MethodParameters;

class MolliePaymentMethods implements ResolverInterface
{
    /**
     * @var Mollie
     */
    private $mollie;

    /**
     * @var MethodParameters
     */
    private $methodParameters;

    /**
     * @var CartInterfaceFactory
     */
    private $cartFactory;

    public function __construct(
        Mollie $mollie,
        MethodParameters $methodParameters,
        CartInterfaceFactory $cartFactory
    ) {
        $this->mollie = $mollie;
        $this->methodParameters = $methodParameters;
        $this->cartFactory = $cartFactory;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $amount = 10;
        $currency = 'EUR';

        if (isset($args['input'], $args['input']['amount'])) {
            $amount = $args['input']['amount'];
        }

        if (isset($args['input'], $args['input']['currency'])) {
            $currency = $args['input']['currency'];
        }

        $parameters = [
            'amount[value]' => number_format($amount, 2, '.', ''),
            'amount[currency]' => $currency,
            'resource' => 'orders',
            'includeWallets' => 'applepay',
        ];

        $parameters = $this->methodParameters->enhance($parameters, $this->cartFactory->create());
        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        $apiMethods = $this->mollie->getMollieApi($storeId)->methods->allActive($parameters);

        $methods = [];
        /** @var Method $method */
        foreach ($apiMethods as $method) {
            $methods[] = [
                'code' => $method->id,
                'name' => $method->description,
                'image' => $method->image->svg,
            ];
        }

        return [
            'methods' => $methods,
        ];
    }
}
