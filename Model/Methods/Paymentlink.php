<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Mollie;
use Magento\Framework\DataObject;

/**
 * Class Paymentlink
 *
 * @package Mollie\Payment\Model\Methods
 */
class Paymentlink extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    const CODE = 'mollie_methods_paymentlink';

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $this->getInfoInstance();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(Order::STATE_PENDING_PAYMENT);

        if ($status = $this->config->statusNewPaymentLink($order->getStoreId())) {
            $stateObject->setStatus($status);
        }
    }

    /**
     * @param DataObject $data
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(DataObject $data)
    {
        $limitedMethods = null;
        parent::assignData($data);

        if ($additionalData = $data->getData('additional_data')) {
            if (isset($additionalData['limited_methods'])) {
                $limitedMethods = $additionalData['limited_methods'];
            }
        }

        $this->getInfoInstance()->setAdditionalInformation('limited_methods', $limitedMethods);

        return $this;
    }
}
