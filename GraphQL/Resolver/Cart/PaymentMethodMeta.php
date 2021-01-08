<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class PaymentMethodMeta implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepository;

    public function __construct(
        \Magento\Framework\View\Asset\Repository $assertRepository
    ) {
        $this->assetRepository = $assertRepository;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $method = $value['code'];
        if (strpos($method, 'mollie_method') !== 0) {
            return ['image' => null];
        }

        $cleanCode = str_replace('mollie_methods_', '', $method);

        return [
            'image' => $this->assetRepository->getUrl('Mollie_Payment::images/methods/' . $cleanCode . '.svg'),
        ];
    }
}
