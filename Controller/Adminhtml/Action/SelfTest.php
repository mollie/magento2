<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Helper\Tests as TestsHelper;
use Mollie\Payment\Service\Mollie\SelfTests\AbstractSelfTest;

/**
 * Class Compatibility
 *
 * @package Mollie\Payment\Controller\Adminhtml\Action
 */
class SelfTest extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var AbstractSelfTest[]
     */
    private $tests;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        MollieHelper $mollieHelper,
        array $tests
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->mollieHelper = $mollieHelper;
        $this->tests = $tests;
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

        $messages = [];
        foreach ($this->tests as $test) {
            $test->execute();

            $messages = array_merge($messages, $test->getMessages());
        }

        $output = '';
        foreach ($messages as $message) {
            $output .= '<div class="mollie-' . $message['type'] . '">' . $message['message'] . '</div>';
        }

        $result->setData(['success' => true, 'msg' => $output]);
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
