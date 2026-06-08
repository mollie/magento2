<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CaptureExpirationWindow extends Field
{
    protected $_template = 'Mollie_Payment::system/config/fieldset/capture-expiration-window.phtml';

    private string $method;

    public function _getElementHtml(AbstractElement $element): string
    {
        [, $method] = explode('mollie_methods_', $element->getFieldConfig()['path']);
        $this->method = $method;

        return $this->_toHtml();
    }

    public function getPaymentMethod(): string
    {
        return $this->method;
    }
}
