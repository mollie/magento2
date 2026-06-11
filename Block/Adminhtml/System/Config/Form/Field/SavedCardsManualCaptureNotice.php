<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SavedCardsManualCaptureNotice extends Field
{
    protected function _getElementHtml(AbstractElement $element): string
    {
        $message = (string) __(
            'Saved cards rely on a mandate that Mollie only creates once the payment is settled, which ' .
            'happens at capture (on invoice or shipment). A card saved on a manual capture order will ' .
            'therefore only become available to the customer after the previous order has been captured.'
        );

        return sprintf(
            '<input type="hidden" id="%s" disabled="disabled"/>' .
            '<div class="message message-warning" style="margin:0">%s</div>',
            $element->getHtmlId(),
            $message
        );
    }

    protected function _renderScopeLabel(AbstractElement $element): string
    {
        return '';
    }

    protected function _renderInheritCheckbox(AbstractElement $element): string
    {
        return '';
    }
}
