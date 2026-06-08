<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroup implements OptionSourceInterface
{
    public function __construct(
        private GroupManagementInterface $groupManagement
    ) {}

    public function toOptionArray(): array
    {
        $groups = $this->groupManagement->getLoggedInGroups();

        $notLoggedInGroup = $this->groupManagement->getNotLoggedInGroup();
        array_unshift($groups, $notLoggedInGroup);

        $output = [];
        foreach ($groups as $group) {
            $output[] = [
                'label' => $group->getCode(),
                'value' => $group->getId(),
            ];
        }

        return $output;
    }
}
