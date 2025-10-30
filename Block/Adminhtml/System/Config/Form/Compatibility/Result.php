<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\System\Config\Form\Compatibility;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Result
 *
 * @package Mollie\Payment\Block\Adminhtml\System\Config\Form\Compatibility
 */
class Result extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::system/config/fieldset/result_compatibility.phtml';
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Result constructor.
     *
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = [],
    ) {
        $this->request = $context->getRequest();
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     *
     * @return bool
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return bool
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
