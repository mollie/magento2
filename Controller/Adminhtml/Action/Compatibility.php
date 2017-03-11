<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Mollie\Payment\Helper\General as MollieHelper;

class Compatibility extends Action
{

    protected $request;
    protected $resultJsonFactory;
    protected $objectManager;
    protected $compatibilityCheckerHelper;

    /**
     * Compatibility constructor.
     *
     * @param Context                $context
     * @param JsonFactory            $resultJsonFactory
     * @param MollieHelper           $compatibilityCheckerHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        MollieHelper $compatibilityCheckerHelper
    ) {
        $this->request = $context->getRequest();
        $this->objectManager = $context->getObjectManager();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mollieHelper = $compatibilityCheckerHelper;
        parent::__construct($context);
    }

    /**
     * Admin controller for compatibility test
     *
     * - Check if Mollie php API is installed
     * - Check for minimum system requirements of API
     *
     * @return $this
     */
    public function execute()
    {

        if (!$this->mollieHelper->checkIfClassExists('Mollie_API_CompatibilityChecker')) {
            $msg = $this->mollieHelper->getPhpApiErrorMessage();
            $return = '<span class="mollie-error">' . $msg . '</span>';
            $result = $this->resultJsonFactory->create();

            return $result->setData(['success' => true, 'msg' => $return]);
        }

        $results = [];
        $compatibilityChecker = $this->objectManager->create('Mollie_API_CompatibilityChecker');

        if (!$compatibilityChecker->satisfiesPhpVersion()) {
            $minPhpVersion = $compatibilityChecker::$MIN_PHP_VERSION;
            $msg = 'Error: The client requires PHP version >= ' . $minPhpVersion . ', you have ' . PHP_VERSION . '.';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        } else {
            $msg = 'Success: PHP version: ' . PHP_VERSION . '.';
            $results[] = '<span class="mollie-success">' . $msg . '</span>';
        }

        if (!$compatibilityChecker->satisfiesJsonExtension()) {
            $msg = 'Error: PHP extension JSON is not enabled. ';
            $msg .= 'Please make sure to enable \'json\' in your PHP configuration.';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        } else {
            $msg = 'Success: JSON is enabled.';
            $results[] = '<span class="mollie-success">' . $msg . '</span>';
        }

        if (!$compatibilityChecker->satisfiesCurlExtension()) {
            $msg = 'Error: PHP extension cURL is not enabled. ';
            $msg .= 'Please make sure to enable \'curl\' in your PHP configuration.';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        } else {
            $msg = 'Success: cURL is enabled.';
            $results[] = '<span class="mollie-success">' . $msg . '</span>';
        }

        if (!$compatibilityChecker->satisfiesCurlFunctions()) {
            $reqCurlFunctions = implode(', ', $compatibilityChecker::$REQUIRED_CURL_FUNCTIONS);
            $msg = 'Error: This client requires the following cURL functions to ';
            $msg .= 'be available: ' . $reqCurlFunctions . '.<br/>';
            $msg .= 'Please check that none of these functions are disabled in your PHP configuration.';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        } else {
            $msg = 'Success: cURL functions are enabled.';
            $results[] = '<span class="mollie-success">' . $msg . '</span>';
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
