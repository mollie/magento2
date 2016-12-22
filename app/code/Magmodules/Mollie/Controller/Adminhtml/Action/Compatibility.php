<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Controller\Adminhtml\Action;

use Mollie_API_CompatibilityChecker as CompatibilityChecker;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Compatibility extends Action
{

    protected $resultJsonFactory;
    protected $request;
    protected $compatibilityChecker;

    /**
     * Compatibility constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CompatibilityChecker $compatibilityChecker
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CompatibilityChecker $compatibilityChecker
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $context->getRequest();
        $this->compatibilityChecker = $compatibilityChecker;
        parent::__construct($context);
    }

    /**
     * Admin controller for compatibility test
     *
     * @return $this
     */
    public function execute()
    {
        $results = [];
        $class = $this->compatibilityChecker;

        if (!$this->compatibilityChecker->satisfiesPhpVersion()) {
            $minPhpVersion = $class::$MIN_PHP_VERSION;
            $msg = 'Error: The client requires PHP version >= ' . $minPhpVersion . ', you have ' . PHP_VERSION . '.';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        } else {
            $msg = 'Success: PHP version: ' . PHP_VERSION . '.';
            $results[] = '<span class="mollie-success">' . $msg . '</span>';
        }

        if (!$this->compatibilityChecker->satisfiesJsonExtension()) {
            $msg = 'Error: PHP extension JSON is not enabled. Please make sure to enable \'json\' in your PHP configuration.';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        } else {
            $msg = 'Success: JSON is enabled.';
            $results[] = '<span class="mollie-success">' . $msg . '</span>';
        }

        if (!$this->compatibilityChecker->satisfiesCurlExtension()) {
            $msg = 'Error: PHP extension cURL is not enabled. Please make sure to enable \'curl\' in your PHP configuration.';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        } else {
            $msg = 'Success: cURL is enabled.';
            $results[] = '<span class="mollie-success">' . $msg . '</span>';
        }

        if (!$this->compatibilityChecker->satisfiesCurlFunctions()) {
            $reqCurlFunctions = implode(', ', $class::$REQUIRED_CURL_FUNCTIONS);
            $msg = 'Error: This client requires the following cURL functions to be available: ' . $reqCurlFunctions . '.<br/>';
            $$msg .= 'Please check that none of these functions are disabled in your PHP configuration.';
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
        return $this->_authorization->isAllowed('Magmodules_mollie::config');
    }
}
