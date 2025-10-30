<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\General;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Mollie\Api\Resources\Method;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\MethodParameters;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class MolliePaymentMethods implements ResolverInterface
{
    public function __construct(
        private MollieApiClient $mollieApiClient,
        private MethodParameters $methodParameters,
        private CartInterfaceFactory $cartFactory,
        private Config $config
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $amount = 10;
        $currency = null;

        if (isset($args['input'], $args['input']['amount'])) {
            $amount = $args['input']['amount'];
        }

        if (isset($args['input'], $args['input']['currency'])) {
            $currency = $args['input']['currency'];
        }

        $storeId = storeId($context->getExtensionAttributes()->getStore()->getId());
        $apiMethods = $this->getMethods($amount, $currency, $storeId) ?? [];

        $methods = [];
        /** @var Method $method */
        foreach ($apiMethods as $method) {
            if (!$this->config->isMethodActive($method->id, $storeId)) {
                continue;
            }

            $methods[] = [
                'code' => $method->id,
                'name' => $this->config->getMethodTitle($method->id, $storeId),
                'image' => $method->image->svg,
            ];
        }

        usort($methods, function (array $a, array $b): int {
            // Lowercase as iDeal would be sorted last because of the lower I.
            return strtolower($a['name']) <=> strtolower($b['name']);
        });

        return [
            'methods' => $methods,
        ];
    }

    private function getMethods(float $amount, ?string $currency, int $storeId): ?array
    {
        $mollieApiClient = $this->mollieApiClient->loadByStore($storeId);

        if ($currency === null) {
            $available = $mollieApiClient->methods->allEnabled();
            $available = array_filter((array) $available, function (Method $method): bool {
                return $method->status == 'activated';
            });

            return $available;
        }

        $parameters = [
            'amount[value]' => number_format($amount, 2, '.', ''),
            'amount[currency]' => $currency,
            'resource' => 'orders',
            'includeWallets' => 'applepay',
        ];

        return (array) $mollieApiClient->methods->allActive(
            $this->methodParameters->enhance($parameters, $this->cartFactory->create()),
        );
    }
}
