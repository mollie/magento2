<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Reminder;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Pending extends AbstractAction
{
    const ADMIN_RESOURCE = 'Mollie_Payment::pending_payment_reminders';

    /**
     * @var PageFactory
     */
    private $pageFactory;

    public function __construct(
        PageFactory $pageFactory,
        Action\Context $context
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Mollie_Payment::pending_payment_reminders');
        $resultPage->getConfig()->getTitle()->prepend(__('Pending Payment Reminders'));

        return $resultPage;
    }
}