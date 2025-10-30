<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Mollie\Payment\Block\Adminhtml\Render\HeaderLabel;
use Mollie\Payment\Config;

class GroupFrontendModel extends Fieldset
{
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        private readonly Config $config,
        array $data = [],
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    protected function _getFrontendClass($element): string
    {
        $parent = parent::_getFrontendClass($element);

        if (
            !$element->getData('original_data') ||
            !isset($element->getData('original_data')['id'])
        ) {
            return $parent;
        }

        $id = $element->getData('original_data')['id'];

        return $parent . ' mollie-method-card ' . $this->getMethodClass($id);
    }

    public function render(AbstractElement $element): string
    {
        $output = parent::render($element);

        $header = $this->_layout->createBlock(HeaderLabel::class, '', ['data' => ['element' => $element]]);

        $newHtml = '
                <dialog class="mollie-method-card-dialog" id="' . $element->getHtmlId() . '_dialog">
                    ' . $header->toHtml() . '
                    <div class="mollie-method-card-content">
                        $1
                    </div>
                </dialog>
            ';

        $output = preg_replace(
            '/(<fieldset[^>]*>.*?<\/fieldset>)/s',
            $newHtml,
            $output,
        );

        return $output;
    }

    protected function _getHeaderTitleHtml($element)
    {
        return '<a id="' . $element->getHtmlId() . '-head"
            data-method="' . $element->getHtmlId() . '"
            class="mollie-method-card-head"
            >' . $element->getLegend() . '</a>';
    }

    private function getMethodClass(string $id): string
    {
        if ($this->config->isMethodActive($id)) {
            return 'mollie-configuration-method-active';
        }

        return 'mollie-configuration-method-inactive';
    }
}
