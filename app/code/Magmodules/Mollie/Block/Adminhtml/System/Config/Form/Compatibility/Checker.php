<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Block\Adminhtml\System\Config\Form\Compatibility;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Checker extends Field
{

    protected $request;
    protected $_template = 'Magmodules_Mollie::system/config/button/button.phtml';

    /**
     * Checker constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->request = $context->getRequest();
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('mollie/action/compatibility');
    }

    /**
     * @return mixed
     */
    public function getButtonHtml()
    {
        $button_data = ['id' => 'compatibility_button', 'label' => __('Self Test')];
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($button_data);

        return $button->toHtml();
    }
}
