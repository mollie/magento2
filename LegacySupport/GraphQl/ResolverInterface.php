<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl\Query;

/**
 * This file is here only because we need to support both Magento 2.2 and Magento 2.3. In Magento 2.3 we want to support
 * GraphQL, but for that we need to implement the ResolverInterface. As this class does not exists in Magento 2.2 the
 * `setup:di:compile` command will fail. That's why we use this trick to make sure this class exists in 2.2.
 */
if (interface_exists('Magento\Framework\GraphQl\Query\ResolverInterface')) {
    return;
}

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Resolver fetches the data and formats it according to the GraphQL schema.
 */
interface ResolverInterface
{
    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|Value
     * @throws \Exception
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    );
}
