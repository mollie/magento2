<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart\Payment;

/**
 * This class is available in default Magento 2.3.3 versions and higher. But due to Composer replace techniques, this
 * class is sometimes missing. So check if it's available, and if not, create an empty variant. It's then only used
 * for class generation in the setup:di:compile process.
 */
if (!interface_exists(AdditionalDataProviderInterface::class)) {
    interface AdditionalDataProviderInterface {}
}
