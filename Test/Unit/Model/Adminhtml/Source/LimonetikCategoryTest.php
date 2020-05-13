<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Unit\Model\Adminhtml\Source;

use Mollie\Payment\Model\Adminhtml\Source\LimonetikCategory;
use Mollie\Payment\Test\Unit\UnitTestCase;

class LimonetikCategoryTest extends UnitTestCase
{
    public function returnsTheRightOptions()
    {
        return [
            [LimonetikCategory::FOOD_AND_DRINKS],
            [LimonetikCategory::HOME_AND_GARDEN],
            [LimonetikCategory::GIFTS_AND_FLOWERS],
            [LimonetikCategory::NULL],
            [LimonetikCategory::CUSTOM_ATTRIBUTE],
        ];
    }

    /**
     * @dataProvider returnsTheRightOptions
     */
    public function testReturnsTheRightOptions($type)
    {
        /** @var LimonetikCategory $instance */
        $instance = $this->objectManager->getObject(LimonetikCategory::class);
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