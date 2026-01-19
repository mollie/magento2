<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order;

use Exception;
use Mollie\Api\Resources\Payment;

class GetSendcloudShippingTitle
{
    public function __construct(
        private readonly ?string $default = null
    ) {}

    public function execute(Payment $payment): string
    {
        if (!property_exists($payment, 'details') ||
            !is_object($payment->details) ||
            !property_exists($payment->details, 'idealExpressMetadata') ||
            !is_object($payment->details->idealExpressMetadata) ||
            !property_exists($payment->details->idealExpressMetadata, 'shipping_method_option') ||
            !is_string($payment->details->idealExpressMetadata->shipping_method_option)
        ) {
            return $this->getDefaultTitle();
        }

        $shippingMethodOption = $payment->details->idealExpressMetadata->shipping_method_option;
        try {
            $shippingMethodOption = json_decode($shippingMethodOption, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to decode shipping method option JSON');
            }
        } catch (Exception $exception) {
            return $this->getDefaultTitle();
        }

        if (array_key_exists('title', $shippingMethodOption) && is_string($shippingMethodOption['title'])) {
            return $shippingMethodOption['title'];
        }

        return $this->getDefaultTitle();
    }

    private function getDefaultTitle(): string
    {
        if ($this->default) {
            return $this->default;
        }

        return __('Sendcloud');
    }
}
