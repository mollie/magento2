<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Methods;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Mollie\Payment\Model\Mollie;

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
    public const CODE = 'mollie_methods_paymentlink';

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject): void
    {
        /** @var Payment $payment */
        $payment = $this->getInfoInstance();

        /** @var Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(Order::STATE_PENDING_PAYMENT);

        if ($status = $this->config->statusNewPaymentLink(storeId($order->getStoreId()))) {
            $stateObject->setStatus($status);
        }
    }

    /**
     * @param DataObject $data
     *
     * @return $this
     * @throws LocalizedException
     */
    public function assignData(DataObject $data): static
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
