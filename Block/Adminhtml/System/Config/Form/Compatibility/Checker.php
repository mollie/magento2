<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\System\Config\Form\Compatibility;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Mollie\Payment\Helper\General as MollieHelper;

/**
 * Class Checker
 *
 * @package Mollie\Payment\Block\Adminhtml\System\Config\Form\Compatibility
 */
class Checker extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::system/config/button/compatibility.phtml';
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * Checker constructor.
     *
     * @param Context      $context
     * @param MollieHelper $mollieHelper
     * @param array        $data
     */
    public function __construct(
        Context $context,
        MollieHelper $mollieHelper,
        array $data = []
    ) {
        $this->request = $context->getRequest();
        $this->mollieHelper = $mollieHelper;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
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
        $storeId = (int)$this->request->getParam('store', 0);
        return $this->getUrl('mollie/action/compatibility/store/' . $storeId);
    }

    /**
     * @return mixed
     */
    public function getButtonHtml()
    {
        try {
            $buttonData = ['id' => 'compatibility_button', 'label' => __('Self Test')];
            $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($buttonData);
            return $button->toHtml();
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }
}
