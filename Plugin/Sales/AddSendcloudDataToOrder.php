<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Data\SendcloudPartnerInterfaceFactory;

class AddSendcloudDataToOrder
{
    public function __construct(
        private SendcloudPartnerInterfaceFactory $sendcloudPartnerFactory,
        private array $partnerData = [],
    ) {}


    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $result): OrderInterface
    {
        $payment = $result->getPayment();
        if (!$payment) {
            return $result;
        }

        $details = $payment->getAdditionalInformation('mollie_ideal_express_metadata');
        if (!$details) {
            return $result;
        }

        $result->getExtensionAttributes()->setSendcloudPartner($this->sendcloudPartnerFactory->create($this->partnerData));
        $result->getExtensionAttributes()->setSendcloudCheckoutPayload($details);

        return $result;
    }

    public function afterGetList(OrderRepositoryInterface $subject, OrderSearchResultInterface $result): OrderSearchResultInterface
    {
        foreach ($result->getItems() as $item) {
            $this->afterGet($subject, $item);
        }

        return $result;
    }
}
