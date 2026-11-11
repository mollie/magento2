<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Exceptions;

use Magento\Framework\Exception\LocalizedException;

/**
 * The express session needs Mollie to collect the email or an address, which only accounts in the Mollie beta
 * support.
 */
class CustomerDetailsRequired extends LocalizedException
{
}
