<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Savedcards;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Mollie\Payment\Config;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private ResultFactory $resultFactory,
        private Config $config,
        private Session $customerSession,
    ) {}

    public function execute(): ResultInterface
    {
        if (!$this->customerSession->authenticate()) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('customer/account/login');
        }

        if (!$this->config->creditcardEnableCustomersApi() || !$this->config->isProductionMode()) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('noroute');
        }

        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set(__('Saved cards'));

        return $page;
    }
}
