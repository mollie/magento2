<?php

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Mollie as MollieModel;

class FetchOrderStatus extends \Magento\Backend\App\Action
{
    /**
     * @var MollieModel
     */
    private $mollieModel;

    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    public function __construct(
        Action\Context $context,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper
    ) {
        parent::__construct($context);
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $orderId = $this->getRequest()->getParam('order_id');

            $message = $this->mollieModel->processTransaction($orderId, 'webhook');

            $this->messageManager->addSuccessMessage(__('The latest status from Mollie has been retrieved'));

            return $result->setData($message);
        } catch (\Exception $exception) {
            $result->setHttpResponseCode(503);

            return $result->setData([
                'error' => true,
                'msg' => $exception->getMessage(),
            ]);
        }
    }
}