<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class AddMollieShipmentIdAttribute implements DataPatchInterface
{
    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    public function apply()
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create();

        /**
         * Add 'mollie_transaction_id' attributes for order
         */
        $salesSetup->addAttribute('shipment', 'mollie_shipment_id', [
            'type' => 'varchar',
            'visible' => false,
            'required' => false
        ]);

        return $this;
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
