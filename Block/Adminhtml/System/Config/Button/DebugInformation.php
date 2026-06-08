<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\System\Config\Button;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DebugInformation extends Field
{
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    public function _getElementHtml(AbstractElement $element): string
    {
        $buttonData = [
            'id' => 'mm-mollie-button_debug-download',
            'label' => __('Download'),
            'onclick' => 'setLocation(\'' . $this->getUrl('mollie/log/download') . '\')',
        ];

        return $this
            ->getLayout()
            ->createBlock(Button::class)
            ->setData($buttonData)
            ->toHtml();
    }
}
