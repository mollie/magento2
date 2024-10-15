<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Quote\Api;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\GooglePay;
use Mollie\Payment\Model\Methods\Pointofsale;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Service\Mollie\PointOfSaleAvailability;

class PaymentMethodManagementPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var MollieConfigProvider
     */
    private $mollieConfigProvider;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var PointOfSaleAvailability
     */
    private $pointOfSaleAvailability;

    public function __construct(
        Config $config,
        MollieConfigProvider $mollieConfigProvider,
        CartRepositoryInterface $cartRepository,
        PointOfSaleAvailability $pointOfSaleAvailability
    ) {
        $this->config = $config;
        $this->mollieConfigProvider = $mollieConfigProvider;
        $this->cartRepository = $cartRepository;
        $this->pointOfSaleAvailability = $pointOfSaleAvailability;
    }

    public function afterGetList(PaymentMethodManagementInterface $subject, $result, $cartId)
    {
        $cart = $this->cartRepository->get($cartId);
        if (!$this->containsMollieMethods($result) || !$this->config->isMethodsApiEnabled((int)$cart->getStoreId())) {
            return $result;
        }

        $activeMethods = $this->mollieConfigProvider->getActiveMethods($cart);

        return array_filter($result, function ($method) use ($activeMethods, $cart) {
            if (!$method instanceof Mollie || $method instanceof GooglePay) {
                return true;
            }

            if ($method instanceof Pointofsale) {
                return $this->pointOfSaleAvailability->isAvailable($cart);
            }

            return array_key_exists($method->getCode(), $activeMethods);
        });
    }

    private function containsMollieMethods(array $list): bool
    {
        $list = array_filter($list, function ($method) {
            return $method instanceof Mollie;
        });

        return count($list);
    }
}
