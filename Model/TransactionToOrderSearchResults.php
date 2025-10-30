<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Api\SearchResults;
use Mollie\Payment\Api\Data\TransactionToOrderSearchResultsInterface;

class TransactionToOrderSearchResults extends SearchResults implements TransactionToOrderSearchResultsInterface
{
}
