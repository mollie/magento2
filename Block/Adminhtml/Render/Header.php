<?php
/**
 * Copyright © 2018 Mollie.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Header
 *
 * @package Mollie\Payment\Block\Adminhtml\Render
 */
class Header extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::system/config/fieldset/header.phtml';

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('Mollie');

        return $this->toHtml();
    }
}
