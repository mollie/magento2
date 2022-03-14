<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mirasvit\Rewards\Helper\Purchase;
use Mollie\Payment\Helper\General;

class MirasvitRewards implements GeneratorInterface
{
    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var bool
     */
    private $forceBaseCurrency;

    /**
     * @var string|null
     */
    private $currency;

    public function __construct(
        General $mollieHelper,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
    }

    public function process(OrderInterface $order, array $orderLines): array
    {
        if (!$this->moduleManager->isEnabled('Mirasvit_Rewards')) {
            return $orderLines;
        }

        // The objectmanager is being used as most of the times this class won't be available. So only load it when we
        // are sure that this module is enabled.
        /** @var Purchase $purchaseHelper */
        // @phpstan-ignore-next-line
        $purchaseHelper = $this->objectManager->create(Purchase::class);

        try {
            $purchase = $purchaseHelper->getByOrder($order);
        } catch (NoSuchEntityException $exception) {
            // No rewards available. Skip it.
            return $orderLines;
        }

        $this->forceBaseCurrency = (bool)$this->mollieHelper->useBaseCurrency($order->getStoreId());
        $this->currency = $this->forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        $amount = $this->forceBaseCurrency ? $purchase->getBaseSpendAmount() : $purchase->getSpendAmount();

        $orderLines[] = [
            'type' => 'surcharge',
            'name' => 'Mirasvit Rewards',
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($this->currency, -$amount),
            'totalAmount' => $this->mollieHelper->getAmountArray($this->currency, -$amount),
            'vatRate' => 0,
            'vatAmount' => $this->mollieHelper->getAmountArray($this->currency, 0.0),
        ];

        return $orderLines;
    }
}
