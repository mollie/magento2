<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;

class ChangeShippingMethodForQuote
{
    /**
     * @var ShippingInformationManagementInterface
     */
    private $shippingInformationManagement;
    /**
     * @var ShippingInformationInterfaceFactory
     */
    private $shippingInformationFactory;

    public function __construct(
        ShippingInformationManagementInterface $shippingInformationManagement,
        ShippingInformationInterfaceFactory $shippingInformationFactory
    ) {
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->shippingInformationFactory = $shippingInformationFactory;
    }

    public function execute(AddressInterface $address, string $identifier): void
    {
        $address->setCollectShippingRates(true);
        $address->setShippingMethod($identifier);

        [$carrierCode, $methodCode] = explode('__SPLIT__', $identifier);
        $shippingInformation = $this->shippingInformationFactory->create([
            'data' => [
                ShippingInformationInterface::SHIPPING_ADDRESS => $address,
                ShippingInformationInterface::SHIPPING_CARRIER_CODE => $carrierCode,
                ShippingInformationInterface::SHIPPING_METHOD_CODE => $methodCode,
            ],
        ]);

        $this->shippingInformationManagement->saveAddressInformation($address->getQuoteId(), $shippingInformation);
    }
}
