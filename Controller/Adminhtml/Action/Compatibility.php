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
class Compatibility extends Action
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
        if (!class_exists('Mollie\Api\CompatibilityChecker', false)) {
            $results = ['<span class="mollie-error">' . $this->mollieHelper->getPhpApiErrorMessage() . '</span>'];
        } else {
            $results = $this->testsHelper->compatibilityChecker();
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
