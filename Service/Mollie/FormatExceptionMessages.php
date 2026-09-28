<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Mollie\Api\Exceptions\ValidationException;

class FormatExceptionMessages
{
    private const GENERIC_ERROR_MESSAGE = 'Your payment was not completed. Please try again or select another payment method.';

    /**
     * Maps the field of a Mollie validation error to a message the customer can act on. The first match wins.
     */
    private const FIELD_ERROR_MESSAGES = [
        '/\.email$/' => 'Please check your email address and try again.',
        '/\.phone$/' => 'Please check your phone number and try again.',
        '/\.postalCode$/' => 'Please check your postal code and try again.',
        '/^billingAddress\./' => 'Please check your billing address and try again.',
        '/^shippingAddress\./' => 'Please check your shipping address and try again.',
        '/^cardToken$/' => 'Please check your card details and try again.',
    ];

    /** @var string[] */
    private array $allowedErrorMessages = [
        'The billing country is not supported for this payment method.',
        'A billing organization name is required for this payment method.',
    ];

    /** @var array<string, string> */
    private array $convertErrorMessages = [
        'The webhook URL is invalid because it is unreachable from Mollie\'s point of view' => 'The webhook URL is invalid because it is unreachable from Mollie\'s point of view. View this article for more information: https://github.com/mollie/magento2/wiki/Webhook-Communication-between-your-Magento-webshop-and-Mollie',
        'The payment was declined due to suspected fraud' => 'Your payment was declined. Please select another payment method.',
    ];

    /**
     * @param string[] $allowedErrorMessages
     */
    public function __construct(
        array $allowedErrorMessages = [],
    ) {
        $this->allowedErrorMessages = array_merge($this->allowedErrorMessages, $allowedErrorMessages);
    }

    public function execute(Exception $exception, ?MethodInterface $methodInstance = null): string
    {
        // Make sure this can be picked up by bin/magento i18n:collect-phrases
        // __('The billing country is not supported for this payment method.')
        // __('A billing organization name is required for this payment method.')
        // __('Your payment was declined. Please select another payment method.')
        // __('Please check your email address and try again.')
        // __('Please check your phone number and try again.')
        // __('Please check your postal code and try again.')
        // __('Please check your billing address and try again.')
        // __('Please check your shipping address and try again.')
        // __('Please check your card details and try again.')

        foreach ($this->allowedErrorMessages as $message) {
            if (stripos($exception->getMessage(), $message) !== false) {
                return __($message)->render();
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
                $methodInstance->getTitle(),
            )->render();
        }

        if ($exception instanceof ValidationException) {
            return $this->formatValidationException($exception);
        }

        if ($exception instanceof LocalizedException) {
            return $exception->getMessage();
        }

        return __(self::GENERIC_ERROR_MESSAGE)->render();
    }

    /**
     * The message of a Mollie exception contains the full request body, which includes customer data like addresses,
     * and the detail is only available in English. So we only use the field to pick a message of our own.
     */
    private function formatValidationException(ValidationException $exception): string
    {
        $field = $exception->getField() ?? '';
        foreach (self::FIELD_ERROR_MESSAGES as $pattern => $message) {
            if (preg_match($pattern, $field) === 1) {
                return __($message)->render();
            }
        }

        return __(self::GENERIC_ERROR_MESSAGE)->render();
    }
}
