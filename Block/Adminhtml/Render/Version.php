<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends Field
{

    protected $mollieHelper;

    /**
     * Version constructor.
     *
     * @param Context      $context
     * @param MollieHelper $mollieHelper
     */
    public function __construct(
        Context $context,
        MollieHelper $mollieHelper
    ) {
        $this->mollieHelper = $mollieHelper;
        parent::__construct($context);
    }

    /**
     * Render block: extension version
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label">' . $element->getData('label') . '</td>';
        $html .= '  <td class="value">' . $this->mollieHelper->getExtensionVersion() . '</td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
