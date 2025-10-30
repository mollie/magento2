<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\System\Config\Form\Extension;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Checker extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::system/config/button/extension-checker-buttons.phtml';

    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    public function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('mollie/action/apikey');
    }

    public function getApiKeyButtonHtml(): string
    {
        $buttonData = ['id' => 'apikey_button', 'label' => __('Test Apikey')];

        return $this
            ->getLayout()
            ->createBlock(Button::class)
            ->setData($buttonData)
            ->toHtml();
    }

    public function getSelfTestButtonHtml(): string
    {
        $buttonData = [
            'id' => 'selftest_button',
            'class' => 'mm-mollie-button',
            'label' => __('Run Self-test'),
            'data_attribute' => [
                'mollie-action' => 'test',
            ],
        ];

        return $this
            ->getLayout()
            ->createBlock(Button::class)
            ->setData($buttonData)
            ->toHtml();
    }
}
