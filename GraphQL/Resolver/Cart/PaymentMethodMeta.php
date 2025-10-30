<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\View\Asset\Repository;

class PaymentMethodMeta implements ResolverInterface
{
    public function __construct(
        private Repository $assetRepository
    ) {}

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ) {
        $method = $value['code'];
        if (strpos($method, 'mollie_method') !== 0) {
            return ['image' => null];
        }

        $cleanCode = str_replace('mollie_methods_', '', $method);
        $path = 'Mollie_Payment::images/methods/' . $cleanCode . '.svg';

        return [
            'image' => $this->assetRepository->getUrlWithParams($path, [
                'area' => 'frontend',
            ]),
        ];
    }
}
