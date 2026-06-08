<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

class PendingPaymentStatus extends Status
{
    /**
     * @var string
     */
    protected $_stateStatuses = Order::STATE_PENDING_PAYMENT;
}
