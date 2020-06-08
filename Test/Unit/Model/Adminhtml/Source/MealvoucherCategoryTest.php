<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Unit\Model\Adminhtml\Source;

use Mollie\Payment\Model\Adminhtml\Source\MealvoucherCategory;
use Mollie\Payment\Test\Unit\UnitTestCase;

class MealvoucherCategoryTest extends UnitTestCase
{
    public function returnsTheRightOptions()
    {
        return [
            [MealvoucherCategory::FOOD_AND_DRINKS],
            [MealvoucherCategory::HOME_AND_GARDEN],
            [MealvoucherCategory::GIFTS_AND_FLOWERS],
            [MealvoucherCategory::NULL],
            [MealvoucherCategory::CUSTOM_ATTRIBUTE],
        ];
    }

    /**
     * @dataProvider returnsTheRightOptions
     */
    public function testReturnsTheRightOptions($type)
    {
        /** @var MealvoucherCategory $instance */
        $instance = $this->objectManager->getObject(MealvoucherCategory::class);
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