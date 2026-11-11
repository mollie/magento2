<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\ViewModel;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\ViewModel\ExpressComponents;

/**
 * The cart page has no Mollie checkout config, so the cart template tells the Express Component whether the beta is
 * on. Without that a guest on the cart page never gets the buttons.
 *
 * @magentoAppArea frontend
 */
class ExpressComponentsTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testTellsTheCartComponentTheBetaIsEnabled(): void
    {
        $this->assertStringContainsString('"isBetaEnabled": true', $this->renderCartTemplate());
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testTellsTheCartComponentTheBetaIsDisabled(): void
    {
        $this->assertStringContainsString('"isBetaEnabled": false', $this->renderCartTemplate());
    }

    private function renderCartTemplate(): string
    {
        /** @var Template $block */
        $block = $this->objectManager->get(LayoutInterface::class)->createBlock(Template::class);
        $block->setTemplate('Mollie_Payment::checkout/cart/express-components.phtml');
        $block->setData('view_model', $this->objectManager->get(ExpressComponents::class));

        return $block->toHtml();
    }
}
