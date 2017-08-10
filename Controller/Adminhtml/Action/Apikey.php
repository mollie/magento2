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

class Apikey extends Action
{

    protected $request;
    protected $resultJsonFactory;
    protected $objectManager;
    protected $mollieHelper;

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
     * @return $this
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
            $results[] = '<span class="mollie-error">' . __('Test Key: Empty value') . '</span>';
        } else {
            if (!preg_match('/^test_\w+$/', $testKey)) {
                $results[] = '<span class="mollie-error">' . __('Test Key: Should start with "test_"') . '</span>';
            } else {
                try {
                    $mollieApi = $this->loadApi($testKey);
                    $mollieApi->issuers->all();
                    $results[] = '<span class="mollie-success">' . __('Test Key: Success!') . '</span>';
                } catch (\Exception $e) {
                    $results[] = '<span class="mollie-error">' . __('Test Key: %1', $e->getMessage()) . '</span>';
                }
            }
        }

        if (empty($liveKey)) {
            $results[] = '<span class="mollie-error">' . __('Live Key: Empty value') . '</span>';
        } else {
            if (!preg_match('/^live_\w+$/', $liveKey)) {
                $results[] = '<span class="mollie-error">' . __('Live Key: Should start with "live_"') . '</span>';
            } else {
                try {
                    $mollieApi = $this->loadApi($liveKey);
                    $mollieApi->issuers->all();
                    $results[] = '<span class="mollie-success">' . __('Live Key: Success!') . '</span>';
                } catch (\Exception $e) {
                    $results[] = '<span class="mollie-error">' . __('Live Key: %1', $e->getMessage()) . '</span>';
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
