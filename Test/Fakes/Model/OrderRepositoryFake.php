<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;

class OrderRepositoryFake extends OrderRepository
{
    private bool $shouldSaveFail = false;

    public function givenSaveFails(): void
    {
        $this->shouldSaveFail = true;
    }

    public function save(OrderInterface $entity)
    {
        if ($this->shouldSaveFail) {
            throw new CouldNotSaveException(__('The order could not be saved.'));
        }

        return parent::save($entity);
    }
}
