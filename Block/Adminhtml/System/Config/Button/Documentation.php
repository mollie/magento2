<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\System\Config\Button;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Mollie\Payment\Config;

/**
 * Version check button class
 */
class Documentation extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::system/config/button/documentation.phtml';

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return mixed
     */
    public function getButtonHtml()
    {
        $buttonData = ['id' => 'mm-mollie-button_version', 'label' => __('Check for latest versions')];
        try {
            $button = $this->getLayout()->createBlock(
                Button::class
            )->setData($buttonData);
            return $button->toHtml();
        } catch (Exception $e) {
            return false;
        }
    }
}
