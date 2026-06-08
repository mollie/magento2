<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Adminhtml\Backend;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Mollie\Payment\Model\Adminhtml\Backend\PreventSavingWhenIoscOneStepCheckoutIsActive;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PreventSavingWhenIoscOneStepCheckoutIsActiveTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method some_example_method
     * @throws LocalizedException
     * @return void
     */
    public function testDoesNothingWhenTheIoscOneStepCheckoutIsNotAvailable(): void
    {
        $moduleManagerMock = $this->createMock(Manager::class);
        $moduleManagerMock->method('isEnabled')->willReturn(false);

        /** @var PreventSavingWhenIoscOneStepCheckoutIsActive $instance */
        $instance = $this->objectManager->create(PreventSavingWhenIoscOneStepCheckoutIsActive::class, [
            'moduleManager' => $moduleManagerMock,
        ]);

        $instance->setPath('payment/mollie_general/default_selected_method');
        $instance->setValue('test');
        $instance->afterSave();

        $this->addToAssertionCount(1);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method some_example_method
     * @throws LocalizedException
     * @return void
     */
    public function testDoesNothingWhenTheIoscOneStepCheckoutIsEnabledButTheValueIsEmpty(): void
    {
        $moduleManagerMock = $this->createMock(Manager::class);
        $moduleManagerMock->method('isEnabled')->willReturn(true);

        /** @var PreventSavingWhenIoscOneStepCheckoutIsActive $instance */
        $instance = $this->objectManager->create(PreventSavingWhenIoscOneStepCheckoutIsActive::class, [
            'moduleManager' => $moduleManagerMock,
        ]);

        $instance->setPath('payment/mollie_general/default_selected_method');
        $instance->setValue(''); // Empty value
        $instance->afterSave();

        $this->addToAssertionCount(1);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method some_example_method
     * @throws LocalizedException
     * @return void
     */
    public function testThrowsExceptionWhenTheIoscOneStepCheckoutIsEnabledButTheValueIsValid(): void
    {
        $moduleManagerMock = $this->createMock(Manager::class);
        $moduleManagerMock->method('isEnabled')->willReturn(true);

        /** @var PreventSavingWhenIoscOneStepCheckoutIsActive $instance */
        $instance = $this->objectManager->create(PreventSavingWhenIoscOneStepCheckoutIsActive::class, [
            'moduleManager' => $moduleManagerMock,
        ]);

        $instance->setPath('payment/mollie_general/default_selected_method');
        $instance->setValue('mollie_methods_ideal'); // Valid value

        $this->expectException(LocalizedException::class);

        $instance->afterSave();
    }
}
