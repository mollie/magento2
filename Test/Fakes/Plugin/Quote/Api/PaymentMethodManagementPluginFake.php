<?php

namespace Mollie\Payment\Test\Fakes\Plugin\Quote\Api;

use Magento\Quote\Api\PaymentMethodManagementInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Plugin\Quote\Api\PaymentMethodManagementPlugin;

class PaymentMethodManagementPluginFake extends PaymentMethodManagementPlugin
{
    /**
     * @var callable
     */
    private $callback;

    public function returnAll(): void
    {
        $this->callback = function ($result) {
            return $result;
        };
    }

    public function dontReturnMollieMethods(): void
    {
        $this->callback = function ($result) {
            return array_filter($result, function ($method) {
                return !$method instanceof Mollie;
            });
        };
    }

    public function afterGetList(PaymentMethodManagementInterface $subject, $result, $cartId)
    {
        if ($this->callback) {
            return call_user_func($this->callback, $result);
        }

        return parent::afterGetList($subject, $result, $cartId);
    }
}
