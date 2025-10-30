<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Helper\Tests as TestsHelper;

/**
 * Class Apikey
 *
 * @package Mollie\Payment\Controller\Adminhtml\Action
 */
class Apikey extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Mollie_Payment::config';

    private RequestInterface $request;

    public function __construct(
        Context $context,
        private JsonFactory $resultJsonFactory,
        private TestsHelper $testsHelper,
        private MollieHelper $mollieHelper,
        private ScopeConfigInterface $scopeConfig,
    ) {
        $this->request = $context->getRequest();

        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        if (!class_exists('Mollie\Api\MollieApiClient')) {
            $apiErrorMsg = ['<span class="mollie-error">' . $this->mollieHelper->getPhpApiErrorMessage() . '</span>'];
            $result->setData(['success' => false, 'msg' => $apiErrorMsg]);

            return $result;
        }

        $testKey = $this->getKey('test');
        $liveKey = $this->getKey('live');
        $results = $this->testsHelper->getMethods($testKey, $liveKey);

        return $result->setData(['success' => true, 'msg' => implode('<br/>', $results)]);
    }

    private function getKey(string $type): string
    {
        if (!$this->request->getParam($type . '_key') || $this->request->getParam($type . '_key') == '******') {
            return $this->scopeConfig->getValue('payment/mollie_general/apikey_' . $type, ScopeInterface::SCOPE_STORE);
        }

        return $this->request->getParam($type . '_key');
    }
}
