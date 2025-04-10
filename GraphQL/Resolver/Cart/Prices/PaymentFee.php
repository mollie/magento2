<?php

namespace Mollie\Payment\GraphQL\Resolver\Cart\Prices;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\Data\CartInterface;

class PaymentFee implements ResolverInterface
{
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (!isset($value['model'])) {
            return [];
        }

        /** @var CartInterface $cart */
        $cart = $value['model'];

        $extensionAttributes = $cart->getExtensionAttributes();
        $currency = $cart->getCurrency()->getStoreCurrencyCode();
        $baseCurrency = $cart->getCurrency()->getBaseCurrencyCode();

        return [
            'fee' => [
                'value' => $extensionAttributes->getMolliePaymentFee(),
                'currency' => $currency,
            ],
            'base_fee' => [
                'value' => $extensionAttributes->getBaseMolliePaymentFee(),
                'currency' => $baseCurrency,
            ],
            'fee_tax' => [
                'value' => $extensionAttributes->getMolliePaymentFeeTax(),
                'currency' => $currency,
            ],
            'base_tax' => [
                'value' => $extensionAttributes->getBaseMolliePaymentFeeTax(),
                'currency' => $baseCurrency,
            ],
        ];
    }
}
