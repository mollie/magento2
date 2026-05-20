<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Savedcards;

use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator as CsrfValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Service\Mollie\RevokeMandate;

class Delete implements AccountInterface, HttpPostActionInterface
{
    public function __construct(
        private RequestInterface $request,
        private ResultFactory $resultFactory,
        private ManagerInterface $messageManager,
        private CsrfValidator $csrfValidator,
        private RevokeMandate $revokeMandate,
        private Config $config,
        private MollieLogger $logger,
    ) {}

    public function execute(): ResultInterface
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->config->creditcardEnableCustomersApi()) {
            return $redirect->setPath('noroute');
        }

        if (!$this->csrfValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));
            return $redirect->setPath('mollie/savedcards/index');
        }

        $mandateId = (string)$this->request->getParam('mandate_id');
        if (!$mandateId) {
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $redirect->setPath('mollie/savedcards/index');
        }

        try {
            $this->revokeMandate->execute($mandateId);
            $this->messageManager->addSuccessMessage(__('The saved card has been removed.'));
        } catch (LocalizedException $e) {
            $this->logger->addErrorLog('RevokeMandate', $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->addErrorLog('RevokeMandate', $e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong while removing the saved card.'));
        }

        return $redirect->setPath('mollie/savedcards/index');
    }
}
