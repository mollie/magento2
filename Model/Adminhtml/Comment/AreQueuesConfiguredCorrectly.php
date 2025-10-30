<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

class AreQueuesConfiguredCorrectly extends AbstractBlock implements CommentInterface
{
    public function __construct(
        Context $context,
        private \Mollie\Payment\Service\Mollie\AreQueuesConfiguredCorrectly $areQueuesConfiguredCorrectly,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function getCommentText($elementValue): string
    {
        if (!$this->areQueuesConfiguredCorrectly->execute()) {
            $message = 'Queues are not configured correctly. ' .
                'Please run the self-test from General for more information.';

            return '<strong style="color:red">' . __($message) . '</strong>';
        }

        return '';
    }
}
