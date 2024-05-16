<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\MethodCode;

class GetApiMethod
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var MethodCode
     */
    private $methodCode;

    public function __construct(
        Config $config,
        MethodCode $methodCode
    ) {
        $this->methodCode = $methodCode;
        $this->config = $config;
    }

    /**
     * @param OrderInterface $order
     * @return string 'order' or 'payment'
     */
    public function execute(OrderInterface $order): string
    {
        $method = $this->methodCode->execute($order);
        $storeId = $order->getStoreId() == null ? null : (int)$order->getStoreId();
        return $this->config->getApiMethod($method, $storeId);
    }
}
