<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Mollie\Payment\Setup\Patch\Data\RenameIdealToIdealWero;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RenameIdealToIdealWeroTest extends IntegrationTestCase
{
    /**
     * @magentoDbIsolation enabled
     */
    public function testUpdatesTheDefaultIdealTitle(): void
    {
        $this->saveTitle('iDeal');

        $this->applyPatch();

        $this->assertEquals('iDeal | Wero', $this->readTitle());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testLeavesCustomizedTitlesUntouched(): void
    {
        $this->saveTitle('iDEAL + €1');

        $this->applyPatch();

        $this->assertEquals('iDEAL + €1', $this->readTitle());
    }

    private function saveTitle(string $title): void
    {
        $this->objectManager->get(WriterInterface::class)
            ->save('payment/mollie_methods_ideal/title', $title);
    }

    private function applyPatch(): void
    {
        $this->objectManager->create(RenameIdealToIdealWero::class)->apply();
    }

    private function readTitle(): string
    {
        $collection = $this->objectManager->create(CollectionFactory::class)->create()
            ->addFieldToFilter('path', ['eq' => 'payment/mollie_methods_ideal/title']);

        return (string)$collection->getFirstItem()->getData('value');
    }
}
