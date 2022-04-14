<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Config;

class TransactionDescription
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        Config $config,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
    }

    public function forRegularTransaction(OrderInterface $order): string
    {
        $storeId = $order->getStoreId();
        $description = $this->config->paymentMethodDescription($order->getPayment()->getMethod(), $storeId);

        if (!trim($description ?? '')) {
            $description = '{ordernumber}';
        }

        $storeName = $this->scopeConfig->getValue(
            Information::XML_PATH_STORE_INFO_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $replacements = [
            '{ordernumber}' => $order->getIncrementId(),
            '{storename}' => $storeName,
            '{customerid}' => $order->getCustomerId(),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $description
        );
    }
}
