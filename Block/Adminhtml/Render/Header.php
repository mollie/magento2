<?php
/**
 * Copyright © 2017 Mollie.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Mollie\Payment\Helper\General as GeneralHelper;
use Magento\Backend\Block\Template\Context;

class Header extends Field
{

    protected $general;
    protected $_template = 'Mollie_Payment::system/config/fieldset/header.phtml';

    /**
     * Header constructor.
     * @param Context $context
     * @param GeneralHelper $general
     */
    public function __construct(
        Context $context,
        GeneralHelper $general
    ) {
        $this->general = $general;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('Mollie');

        return $this->toHtml();
    }
}
