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
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * ApiKey constructor.
     *
     * @param Context      $context
     * @param JsonFactory  $resultJsonFactory
     * @param MollieHelper $mollieHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        MollieHelper $mollieHelper
    ) {
        $this->request = $context->getRequest();
        $this->objectManager = $context->getObjectManager();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mollieHelper = $mollieHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {

        if (!$this->mollieHelper->checkIfClassExists('Mollie_API_Client')) {
            $msg = $this->mollieHelper->getPhpApiErrorMessage();
            $return = '<span class="mollie-error">' . $msg . '</span>';
            $result = $this->resultJsonFactory->create();

            return $result->setData(['success' => true, 'msg' => $return]);
        }

        $testKey = $this->request->getParam('test_key');
        $liveKey = $this->request->getParam('live_key');

        $results = [];

        if (empty($testKey)) {
            $results[] = '<span class="mollie-error">' . __('Test API-key: Empty value') . '</span>';
        } else {
            if (!preg_match('/^test_\w+$/', $testKey)) {
                $results[] = '<span class="mollie-error">' . __('Test API-key: Should start with "test_"') . '</span>';
            } else {
                try {
                    $availableMethods = [];
                    $mollieApi = $this->loadApi($testKey);
                    $methods = $mollieApi->methods->all();

                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

                    if (empty($availableMethods)) {
                        $msg = __('Enabled Methods: None, Please enable the payment methods in your Mollie dashboard.');
                        $methodsMsg = '<span class="enabled-methods-error">' . $msg . '</span>';
                    } else {
                        $msg = __('Enabled Methods') . ': ' . implode(', ', $availableMethods);
                        $methodsMsg = '<span class="enabled-methods">' . $msg . '</span>';
                    }

                    $results[] = '<span class="mollie-success">' . __('Test API-key: Success!') . $methodsMsg . '</span>';
                } catch (\Exception $e) {
                    $results[] = '<span class="mollie-error">' . __('Test API-key: %1', $e->getMessage()) . '</span>';
                }
            }
        }

        if (empty($liveKey)) {
            $results[] = '<span class="mollie-error">' . __('Live API-key: Empty value') . '</span>';
        } else {
            if (!preg_match('/^live_\w+$/', $liveKey)) {
                $results[] = '<span class="mollie-error">' . __('Live API-key: Should start with "live_"') . '</span>';
            } else {
                try {
                    $availableMethods = [];
                    $mollieApi = $this->loadApi($liveKey);
                    $methods = $mollieApi->methods->all();
                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

                    if (empty($availableMethods)) {
                        $msg = __('Enabled Methods: None, Please enable the payment methods in your Mollie dashboard.');
                        $methodsMsg = '<span class="enabled-methods-error">' . $msg . '</span>';
                    } else {
                        $msg = __('Enabled Methods') . ': ' . implode(', ', $availableMethods);
                        $methodsMsg = '<span class="enabled-methods">' . $msg . '</span>';
                    }

                    $results[] = '<span class="mollie-success">' . __('Live API-key: Success!') . $methodsMsg . '</span>';
                } catch (\Exception $e) {
                    $results[] = '<span class="mollie-error">' . __('Live API-key: %1', $e->getMessage()) . '</span>';
                }
            }
        }

        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => true, 'msg' => implode('<br/>', $results)]);
    }

    /**
     * @param $apiKey
     *
     * @return mixed
     */
    protected function loadApi($apiKey)
    {
        $mollieApi = $this->objectManager->create('Mollie_API_Client');
        $mollieApi->setApiKey($apiKey);
        $mollieApi->addVersionString('Magento/' . $this->mollieHelper->getMagentoVersion());
        $mollieApi->addVersionString('MollieMagento2/' . $this->mollieHelper->getExtensionVersion());

        return $mollieApi;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mollie_Payment::config');
    }
}
