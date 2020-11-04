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
 * Class Compatibility
 *
 * @package Mollie\Payment\Controller\Adminhtml\Action
 */
class SelfTest extends Action
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
     * Compatibility constructor.
     *
     * @param Context      $context
     * @param JsonFactory  $resultJsonFactory
     * @param TestsHelper  $testsHelper
     * @param MollieHelper $mollieHelper
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
     * Admin controller for compatibility test
     *
     * - Check if Mollie php API is installed
     * - Check for minimum system requirements of API
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!class_exists('Mollie\Api\CompatibilityChecker')) {
            return $this->getPhpApiErrorMessage($result);
        }

        $compatibilityResult = $this->testsHelper->compatibilityChecker();
        $result->setData(['success' => true, 'msg' => implode('<br/>', $compatibilityResult)]);
        return $result;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mollie_Payment::config');
    }

    /**
     * @param \Magento\Framework\Controller\Result\Json $result
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function getPhpApiErrorMessage(\Magento\Framework\Controller\Result\Json $result)
    {
        $results = ['<span class="mollie-error">' . $this->mollieHelper->getPhpApiErrorMessage() . '</span>'];

        if (stripos(__DIR__, 'app/code') !== false) {
            $msg = __('Warning: We recommend to install the Mollie extension using Composer, currently it\'s installed in the app/code folder.');
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        }

        $result->setData(['success' => true, 'msg' => implode('<br/>', $results)]);
        return $result;
    }
}
