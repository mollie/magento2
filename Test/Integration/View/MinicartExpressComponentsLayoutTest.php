<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\View;

use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Result\PageFactory;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * Without the beta Mollie cannot collect the email of a guest, so the minicart must know whether the beta is on to
 * skip the session request for guests. The setting is in the layout, so it also works on full page cached pages.
 *
 * @magentoAppArea frontend
 */
class MinicartExpressComponentsLayoutTest extends IntegrationTestCase
{
    public function testTellsTheMinicartTheBetaIsDisabled(): void
    {
        $this->assertFalse($this->getMinicartComponentConfig(['mollie_express_components'])['isBetaEnabled']);
    }

    public function testTellsTheMinicartTheBetaIsEnabled(): void
    {
        $config = $this->getMinicartComponentConfig([
            'mollie_express_components',
            'mollie_express_components_beta_minicart',
        ]);

        $this->assertTrue($config['isBetaEnabled']);
    }

    public function testDisablesTheMinicartComponentWhenTheMinicartPlacementIsOff(): void
    {
        $config = $this->getMinicartComponentConfig([
            'mollie_express_components',
            'mollie_express_components_minicart_disabled',
        ]);

        $this->assertTrue($config['componentDisabled']);
    }

    private function getMinicartComponentConfig(array $handles): array
    {
        $this->objectManager->get(DesignInterface::class)->setDesignTheme('Magento/luma', 'frontend');

        $page = $this->objectManager->create(PageFactory::class)->create();
        $page->addHandle($handles);
        $layout = $page->getLayout();

        $jsLayout = json_decode($layout->getBlock('minicart')->getJsLayout(), true);

        return $jsLayout['components']['minicart_content']['children']['mollie_express_components']['config'];
    }
}
