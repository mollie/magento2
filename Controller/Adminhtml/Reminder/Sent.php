<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Reminder;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;

class Sent extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Mollie_Payment::sent_payment_reminders';

    public function __construct(
        private PageFactory $pageFactory,
        Context $context,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResponseInterface
    {
        /** @var Page $resultPage */
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Mollie_Payment::sent_payment_reminders');
        $resultPage->getConfig()->getTitle()->prepend(__('Sent Payment Reminders'));

        return $resultPage;
    }
}
