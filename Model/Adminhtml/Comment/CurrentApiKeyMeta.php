<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

class CurrentApiKeyMeta extends AbstractBlock implements CommentInterface
{
    public function __construct(
        Context $context,
        private EncryptorInterface $encryptor,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function getCommentText($elementValue)
    {
        if (empty($elementValue)) {
            return '';
        }

        $start = substr($elementValue, 0, 5);
        $end = substr($elementValue, -4);

        return __('The current value starts with <strong>%1</strong> and ends on <strong>%2</strong>', $start, $end);
    }
}
