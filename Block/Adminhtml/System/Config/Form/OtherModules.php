<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\System\Config\Form;

use Composer\InstalledVersions;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use OutOfBoundsException;

class OtherModules extends Field
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        private ModuleListInterface $moduleList,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::system/config/fieldset/other-modules.phtml';

    public function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    public function formatVersionNumber(string $value): string
    {
        if ((bool)preg_match('/^\d+\.\d+(\.\d+)*(-[a-zA-Z]+[\w.]*)?$/', $value)) {
            return 'v' . $value;
        }

        return $value;
    }

    public function getModules(): array
    {
        return [
            [
                'name' => 'Mollie Analytics',
                'description' => 'Track payment analytics and gain insights into your store\'s transaction data.',
                'composer' => 'mollie/magento2-analytics',
                'module' => 'Mollie_Analytics',
                'url' => 'https://github.com/mollie/magento2-analytics',
                'currentVersion' => $this->getCurrentModuleVersion('mollie/magento2-analytics'),
            ],
            [
                'name' => 'Mollie Hyv&auml; Checkout',
                'description' => 'Optimized checkout experience for Hyv&auml;-powered stores.',
                'composer' => 'mollie/magento2-hyva-checkout',
                'module' => 'Mollie_HyvaCheckout',
                'url' => 'https://github.com/mollie/magento2-hyva-checkout',
                'currentVersion' => $this->getCurrentModuleVersion('mollie/magento2-hyva-checkout'),
            ],
            [
                'name' => 'Mollie Hyv&auml; Compatibility',
                'description' => 'Seamless integration of Mollie payments with the Hyv&auml; theme.',
                'composer' => 'mollie/magento2-hyva-compatibility',
                'module' => 'Mollie_HyvaCompatibility',
                'url' => 'https://github.com/mollie/magento2-hyva-compatibility',
                'currentVersion' => $this->getCurrentModuleVersion('mollie/magento2-hyva-compatibility'),
            ],
            [
                'name' => 'Mollie Hyv&auml; React Checkout',
                'description' => 'React-based checkout integration for Hyv&auml; with Mollie payments.',
                'composer' => 'mollie/magento2-hyva-react-checkout',
                'module' => 'Mollie_HyvaReactCheckout',
                'url' => 'https://github.com/mollie/magento2-hyva-react-checkout',
                'currentVersion' => $this->getCurrentModuleVersion('mollie/magento2-hyva-react-checkout'),
            ],
            [
                'name' => 'Mollie Multi Shipping',
                'description' => 'Support multiple shipping addresses in a single order with Mollie payments.',
                'composer' => 'mollie/magento2-multishipping',
                'module' => 'Mollie_Multishipping',
                'url' => 'https://github.com/mollie/magento2-multishipping',
                'currentVersion' => $this->getCurrentModuleVersion('mollie/magento2-multishipping'),
            ],
            [
                'name' => 'Mollie Subscriptions',
                'description' => 'Offer subscription-based products and recurring payments through Mollie.',
                'composer' => 'mollie/magento2-subscriptions',
                'module' => 'Mollie_Subscriptions',
                'url' => 'https://github.com/mollie/magento2-subscriptions',
                'currentVersion' => $this->getCurrentModuleVersion('mollie/magento2-subscriptions'),
            ],
        ];
    }

    public function isModuleEnabled(string $moduleName): bool
    {
        return $this->moduleList->has($moduleName);
    }

    private function getCurrentModuleVersion(string $name): ?string
    {
        try {
            return InstalledVersions::getVersion($name);
        } catch (OutOfBoundsException) {
            return null;
        }
    }
}
