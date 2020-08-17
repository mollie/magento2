<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Unit\Model\Adminhtml\Source;

use Mollie\Payment\Model\Adminhtml\Source\VoucherCategory;
use Mollie\Payment\Test\Unit\UnitTestCase;

class VoucherCategoryTest extends UnitTestCase
{
    public function returnsTheRightOptions()
    {
        require_once __DIR__ . '/../../../../../Model/Adminhtml/Source/VoucherCategory.php';

        return [
            [VoucherCategory::MEAL],
            [VoucherCategory::ECO],
            [VoucherCategory::GIFT],
            [VoucherCategory::NULL],
            [VoucherCategory::CUSTOM_ATTRIBUTE],
        ];
    }

    /**
     * @dataProvider returnsTheRightOptions
     */
    public function testReturnsTheRightOptions($type)
    {
        /** @var VoucherCategory $instance */
        $instance = $this->objectManager->getObject(VoucherCategory::class);
        $options = $instance->toOptionArray();

        foreach ($options as $option) {
            if ($option['value'] == $type) {
                $this->addToAssertionCount(1);
                return;
            }
        }

        $this->fail('We expected ' . $type . ' to be present in the options');
    }
}