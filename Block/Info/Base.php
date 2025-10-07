<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Info;

use Exception;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Methods\Billie;
use Mollie\Payment\Model\Methods\In3;
use Mollie\Payment\Model\Methods\Klarna;
use Mollie\Payment\Model\Methods\Klarnapaylater;
use Mollie\Payment\Model\Methods\Klarnapaynow;
use Mollie\Payment\Model\Methods\Klarnasliceit;
use Mollie\Payment\Model\Methods\Riverty;
use Mollie\Payment\Service\Magento\PaymentLinkUrl;

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
     * @var Registry
     */
    private $registry;
    /**
     * @var PriceCurrencyInterface
     */
    private $price;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var PaymentLinkUrl
     */
    private $paymentLinkUrl;

    public function __construct(
        Context $context,
        Config $config,
        MollieHelper $mollieHelper,
        Registry $registry,
        PriceCurrencyInterface $price,
        PaymentLinkUrl $paymentLinkUrl
    ) {
        parent::__construct($context);
        $this->mollieHelper = $mollieHelper;
        $this->timezone = $context->getLocaleDate();
        $this->registry = $registry;
        $this->price = $price;
        $this->config = $config;
        $this->paymentLinkUrl = $paymentLinkUrl;
    }

    public function getCheckoutType(): ?string
    {
        try {
            return $this->getInfo()->getAdditionalInformation('checkout_type');
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }
    }

    public function getExpiresAt(): ?string
    {
        try {
            if ($expiresAt = $this->getInfo()->getAdditionalInformation('expires_at')) {
                return $this->timezone->date($expiresAt)->format(DateTime::DATETIME_PHP_FORMAT);
            }
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return null;
    }

    public function getPaymentLink($storeId = null): ?string
    {
        if (!$this->config->addPaymentLinkMessage($storeId)) {
            return null;
        }

        return str_replace(
            '%link%',
            $this->getPaymentLinkUrl(),
            $this->config->paymentLinkMessage($storeId)
        );
    }

    public function getPaymentLinkUrl(): string
    {
        return $this->paymentLinkUrl->execute((int)$this->getInfo()->getParentId());
    }

    public function getCheckoutUrl(): ?string
    {
        try {
            return $this->getInfo()->getAdditionalInformation('checkout_url');
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }
    }

    public function getPaymentStatus(): ?string
    {
        try {
            return $this->getInfo()->getAdditionalInformation('payment_status');
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }
    }

    public function getDashboardUrl(): ?string
    {
        try {
            return $this->getInfo()->getAdditionalInformation('dashboard_url');
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }
    }

    public function getPayPalReference(): ?string
    {
        try {
            $details = $this->getInfo()->getAdditionalInformation('details');
            if (is_string($details)) {
                $details = json_decode($details, true);
            }

            if (!is_array($details) || !array_key_exists('paypalReference', $details)) {
                return null;
            }

            return $details['paypalReference'];
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }
    }

    public function getChangePaymentStatusUrl(): ?string
    {
        try {
            return (string)$this->getInfo()->getAdditionalInformation('mollie_change_payment_state_url');
        } catch (Exception $exception) {
            return null;
        }
    }

    public function getMollieId(): ?string
    {
        try {
            return $this->getInfo()->getAdditionalInformation('mollie_id');
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isBuyNowPayLaterMethod(): bool
    {
        try {
            $code = $this->getInfo()->getMethod();
            $methods = [
                Billie::CODE,
                In3::CODE,
                Klarna::CODE,
                Klarnapaylater::CODE,
                Klarnasliceit::CODE,
                Klarnapaynow::CODE,
                Riverty::CODE,
            ];

            if (in_array($code, $methods)) {
                return true;
            }
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return false;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentImage(): string
    {
        $code = $this->getInfo()->getMethod();
        if (strpos($code, 'mollie_methods_') !== false) {
            $code = str_replace('mollie_methods_', '', $code);
        }

        return $code . '.svg';
    }

    public function getOrderId(): ?string
    {
        try {
            return $this->getInfo()->getParentId();
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }
    }

    public function getAmountPaidByVoucher()
    {
        if (!$this->getOrder()) {
            return false;
        }

        return $this->getOrder()->getGrandTotal() - $this->getRemainderAmount();
    }

    public function getRemainderAmount()
    {
        try {
            return $this->getInfo()->getAdditionalInformation('remainder_amount');
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return false;
    }

    public function formatPrice($amount)
    {
        return $this->price->format($amount);
    }

    private function getOrder(): ?OrderInterface
    {
        return $this->registry->registry('current_order');
    }
}
