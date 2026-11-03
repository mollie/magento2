<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Block\Adminhtml\System\Config;

use Magento\Config\Model\Config\Structure\Element\Group;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\View\LayoutInterface;
use Mollie\Payment\Block\Adminhtml\System\Config\GroupFrontendModel;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GroupFrontendModelTest extends IntegrationTestCase
{
    private const EXPRESS_COMPONENTS_ID = 'mollie_methods_expresscomponents';

    /**
     * @magentoAppArea adminhtml
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 0
     */
    public function testShowsTheExpressComponentsCardWhenTheMethodIsInactive(): void
    {
        $html = $this->render(self::EXPRESS_COMPONENTS_ID);

        $this->assertStringNotContainsString('mollie-method-card hidden', $html);
        $this->assertStringContainsString(
            'class="section-config mollie-method-card mollie-configuration-method-inactive"',
            $html
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     */
    public function testMarksTheExpressComponentsCardAsActiveWhenTheMethodIsActive(): void
    {
        $html = $this->render(self::EXPRESS_COMPONENTS_ID);

        $this->assertStringContainsString(
            'class="section-config mollie-method-card mollie-configuration-method-active"',
            $html
        );
    }

    private function render(string $methodId): string
    {
        $element = $this->objectManager->create(Fieldset::class, [
            'data' => [
                'html_id' => 'mollie_payment_methods_' . $methodId,
                'legend' => 'Express Components',
                'expanded' => true,
                'original_data' => ['id' => $methodId],
            ],
        ]);
        $element->setForm($this->objectManager->create(Form::class));

        /** @var GroupFrontendModel $block */
        $block = $this->objectManager->get(LayoutInterface::class)->createBlock(GroupFrontendModel::class);
        $block->setGroup($this->createGroup($methodId));

        return $block->render($element);
    }

    private function createGroup(string $methodId): Group
    {
        /** @var Group $group */
        $group = $this->objectManager->create(Group::class);
        $group->setData(['id' => $methodId], 'default');

        return $group;
    }
}
