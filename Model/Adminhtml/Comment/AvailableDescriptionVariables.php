<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\View\Element\AbstractBlock;

class AvailableDescriptionVariables extends AbstractBlock implements CommentInterface
{
    public function getCommentText($elementValue)
    {
        return __(
            'The description to be used for this transaction. These variables are available:<br><br>' .
            '<strong>{ordernumber}</strong>: The order number for this transaction.<br>' .
            '<strong>{storename}</strong>: The name of the store.<br>' .
            '<strong>{customerid}</strong>: The ID of the customer. Is empty when the customer is a guest.<br><br>' .
            '(Note: This only works when the method is set to Payments API)'
        );
    }
}
