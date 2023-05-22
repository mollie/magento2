<?php

namespace Mollie\Payment\Service\Mollie;

use Magento\Payment\Model\MethodInterface;

class FormatExceptionMessages
{
    private $allowedErrorMessages = [
        'The billing country is not supported for this payment method.',
        'A billing organization name is required for this payment method.',
    ];

    private $convertErrorMessages = [
        'The webhook URL is invalid because it is unreachable from Mollie\'s point of view' => 'The webhook URL is invalid because it is unreachable from Mollie\'s point of view. View this article for more information: https://github.com/mollie/magento2/wiki/Webhook-Communication-between-your-Magento-webshop-and-Mollie',
    ];

    public function execute(\Exception $exception, MethodInterface $methodInstance = null): string
    {
        foreach ($this->allowedErrorMessages as $message) {
            if (stripos($exception->getMessage(), $message) !== false) {
                return $message;
            }
        }

        foreach ($this->convertErrorMessages as $search => $replacement) {
            if (stripos($exception->getMessage(), $search) !== false) {
                return __($replacement)->render();
            }
        }

        if ($methodInstance && stripos($exception->getMessage(), 'cURL error 28') !== false) {
            return __(
                'A Timeout while connecting to %1 occurred, this could be the result of an outage. ' .
                'Please try again or select another payment method.',
                $methodInstance->getTitle()
            )->render();
        }

        return $exception->getMessage();
    }
}
