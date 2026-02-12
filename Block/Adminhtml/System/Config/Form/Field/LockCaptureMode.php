<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class LockCaptureMode extends Field
{
    protected function _getElementHtml(AbstractElement $element): string
    {
        $path = $element->getFieldConfig()['path'];

        // This is configured in etc/config.xml
        $configPath = str_replace('mollie_payment_methods', 'payment', $path) . '/can_change_capture_method';
        $canChangeCaptureMethod = $this->_scopeConfig->getValue($configPath);

        if ($canChangeCaptureMethod !== '1') {
            $element->setDisabled(true);
            $element->setReadonly(true);
        }

        return parent::_getElementHtml($element);
    }
}
