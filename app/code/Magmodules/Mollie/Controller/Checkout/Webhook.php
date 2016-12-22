<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Controller\Checkout;

use Magmodules\Mollie\Model\Mollie as MollieModel;
use Magmodules\Mollie\Helper\General as MollieHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Webhook extends Action
{
    
    protected $checkoutSession;
    protected $logger;
    protected $paymentHelper;
    protected $mollieModel;
    protected $mollieHelper;

    /**
     * Webhook constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param MollieModel $mollieModel
     * @param MollieHelper $mollieHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        parent::__construct($context);
    }

    /**
     * Mollie webhook
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (!empty($params['testByMollie'])) {
            die('OK');
        }

        if (!empty($params['id'])) {
            try {
                $orderId = $this->mollieModel->getOrderIdByTransactionId($params['id']);
                if ($orderId) {
                    $this->mollieModel->processTransaction($orderId, 'webhook');
                }
            } catch (\Exception $e) {
                $this->mollieHelper->addTolog('error', $e->getMessage());
            }
        }
    }
}
