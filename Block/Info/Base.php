<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Info;

use Magento\Payment\Block\Info;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Stdlib\DateTime;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Methods\Klarnapaylater;
use Mollie\Payment\Model\Methods\Klarnasliceit;

class Base extends Info
{

    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::info/mollie_base.phtml';
    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * Base constructor.
     *
     * @param Context      $context
     * @param MollieHelper $mollieHelper
     */
    public function __construct(
        Context $context,
        MollieHelper $mollieHelper
    ) {
        parent::__construct($context);
        $this->mollieHelper = $mollieHelper;
        $this->timezone = $context->getLocaleDate();
    }

    /**
     * @return string
     */
    public function getCheckoutType()
    {
        try {
            $checkoutType = $this->getInfo()->getAdditionalInformation('checkout_type');
            return $checkoutType;
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getExpiresAt()
    {
        try {
            if ($expiresAt = $this->getInfo()->getAdditionalInformation('expires_at')) {
                return $this->timezone->date($expiresAt)->format(DateTime::DATETIME_PHP_FORMAT);
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getPaymentLink()
    {
        if ($checkoutUrl = $this->getCheckoutUrl()) {
            return $this->mollieHelper->getPaymentLinkMessage($checkoutUrl);
        }
    }

    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        try {
            $checkoutUrl = $this->getInfo()->getAdditionalInformation('checkout_url');
            return $checkoutUrl;
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getPaymentStatus()
    {
        try {
            $paymentStatus = $this->getInfo()->getAdditionalInformation('payment_status');
            return $paymentStatus;
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    public function isKlarnaMethod()
    {
        try {
            $code = $this->getInfo()->getMethod();
            if ($code == Klarnapaylater::METHOD_CODE || $code == Klarnasliceit::METHOD_CODE) {
                return true;
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return false;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentImage()
    {
        $code = $this->getInfo()->getMethod();
        if (strpos($code, 'mollie_methods_') !== false) {
            $code = str_replace('mollie_methods_', '', $code);
        }

        return $code . '.png';
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        try {
            return $this->getInfo()->getParentId();
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }
}
