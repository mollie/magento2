<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class PaymentMethodsHeader extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::system/config/fieldset/payment-methods-header.phtml';

    public function render(AbstractElement $element)
    {
        $element->addClass('Mollie');

        return $this->toHtml();
    }
}