<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\View\Element\AbstractBlock;

class AvailableConsentVariables extends AbstractBlock implements CommentInterface
{
    public function getCommentText($elementValue)
    {
        return __(
            'These placeholders are available:<br><br>' .
            '<strong>{{tradingname}}</strong>: Replaced with the store name.<br>' .
            '<strong>{{supportcontact}}</strong>: Replaced with the general contact email address.<br><br>' .
            'To add a link, use <strong>[link text](https://example.com)</strong> — for example:<br>' .
            '<code>[privacy policy](https://example.com/privacy-policy)</code>',
        );
    }
}
