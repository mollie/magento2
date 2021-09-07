<?php

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Mollie\Payment\Model\Mollie as MollieModel;

class FetchOrderStatus extends Action
{
    /**
     * @var MollieModel
     */
    private $mollieModel;

    public function __construct(
        Action\Context $context,
        MollieModel $mollieModel
    ) {
        parent::__construct($context);
        $this->mollieModel = $mollieModel;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $orderId = $this->getRequest()->getParam('order_id');

            $message = '';
            $orderHasUpdate = $this->mollieModel->orderHasUpdate($orderId);
            $this->throwExceptionIfErrorPresent($orderHasUpdate);
            if ($orderHasUpdate) {
                $message = $this->mollieModel->processTransaction($orderId, 'webhook');
                $this->throwExceptionIfErrorPresent($message);
            }

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

    private function throwExceptionIfErrorPresent($message): void
    {
        if (!is_array($message) || !isset($message['error']) || $message['error'] !== true) {
            return;
        }

        throw new \Exception($message['msg']);
    }
}
