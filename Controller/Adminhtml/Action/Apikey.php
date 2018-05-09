<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
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
     * Apikey constructor.
     *
     * @param Context       $context
     * @param JsonFactory   $resultJsonFactory
     * @param TestsHelper $testsHelper
     * @param MollieHelper  $mollieHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TestsHelper $testsHelper,
        MollieHelper $mollieHelper
    ) {
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->testsHelper = $testsHelper;
        $this->mollieHelper = $mollieHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        if (!class_exists('Mollie\Api\MollieApiClient', false)) {
            $results = ['<span class="mollie-error">' . $this->mollieHelper->getPhpApiErrorMessage() . '</span>'];
        } else {
            $testKey = $this->request->getParam('test_key');
            $liveKey = $this->request->getParam('live_key');
            $results = $this->testsHelper->getMethods($testKey, $liveKey);
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true, 'msg' => implode('<br/>', $results)]);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mollie_Payment::config');
    }
}
