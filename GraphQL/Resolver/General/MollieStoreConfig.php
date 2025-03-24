<?php

namespace Mollie\Payment\GraphQL\Resolver\General;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mollie\Payment\Config;

class MollieStoreConfig implements ResolverInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return [
            'profile_id' => $this->config->getProfileId(),
            'live_mode' => $this->config->isProductionMode(),
        ];
    }
}
