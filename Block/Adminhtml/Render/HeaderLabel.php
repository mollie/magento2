<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\Render;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Element\Template;

/**
 * Class Header
 *
 * @package Mollie\Payment\Block\Adminhtml\Render
 * @method getTitle() string
 */
class HeaderLabel extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::system/config/fieldset/header-label.phtml';

    public function _toHtml(): string
    {
        /** @var AbstractElement $element */
        $element = $this->getData('element');

        $path = str_replace(
            'mollie_payment_methods_',
            'payment/',
            $element->getData('html_id'),
        );

        $title = $this->_scopeConfig->getValue($path . '/title');
        $this->setData('title', $title);

        return parent::_toHtml();
    }
}
