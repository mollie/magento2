<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Service\Order\SecondChanceEmail;

class SendSecondChanceEmail extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $repository;

    /**
     * @var SecondChanceEmail
     */
    private $secondChanceEmail;

    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $repository,
        SecondChanceEmail $secondChanceEmail
    ) {
        parent::__construct($context);
        $this->secondChanceEmail = $secondChanceEmail;
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->repository->get($id);

        $this->secondChanceEmail->send($order);

        $this->messageManager->addSuccessMessage(__('The payment reminder email was successfully send'));

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
