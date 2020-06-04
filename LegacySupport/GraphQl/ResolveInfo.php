<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl\Schema\Type;

/**
 * This file is here only because we need to support both Magento 2.2 and Magento 2.3. In Magento 2.3 we want to support
 * GraphQL, but for that we need to implement the ResolverInterface. As this class does not exists in Magento 2.2 the
 * `setup:di:compile` command will fail. That's why we use this trick to make sure this class exists in 2.2.
 */
if (\class_exists('\Magento\Framework\GraphQl\Schema\Type\ResolveInfo')) {
    return;
}

/**
 * Wrapper for GraphQl ResolveInfo
 */
class ResolveInfo
{

}
