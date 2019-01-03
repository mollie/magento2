<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

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
    protected $_code = 'mollie_methods_paymentlink';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = \Mollie\Payment\Block\Info\Paymentlink::class;
    /**
     * Info instructions form path
     *
     * @var string
     */
    protected $_formBlockType = \Mollie\Payment\Block\Form\Paymentlink::class;

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
        $order->save();

        $this->startTransaction($order);
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
