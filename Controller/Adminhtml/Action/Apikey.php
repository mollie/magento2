<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Helper\Tests as TestsHelper;

/**
 * Class Apikey
 *
 * @package Mollie\Payment\Controller\Adminhtml\Action
 */
class Apikey extends Action
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var TestsHelper
     */
    private $testsHelper;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Apikey constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TestsHelper $testsHelper
     * @param MollieHelper $mollieHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TestsHelper $testsHelper,
        MollieHelper $mollieHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->testsHelper = $testsHelper;
        $this->mollieHelper = $mollieHelper;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
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

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mollie_Payment::config');
    }

    private function getKey(string $type): string
    {
        if (!$this->request->getParam($type . '_key') || $this->request->getParam($type . '_key') == '******') {
            return $this->scopeConfig->getValue('payment/mollie_general/apikey_' . $type, ScopeInterface::SCOPE_STORE);
        }

        return $this->request->getParam($type . '_key');
    }
}
