<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\GetIssuers;

class AvailableIssuers implements ResolverInterface
{
    /**
     * @var Mollie
     */
    private $mollieModel;

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var GetIssuers
     */
    private $getIssuers;

    public function __construct(
        Mollie $mollieModel,
        General $mollieHelper,
        GetIssuers $getIssuers
    ) {
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->getIssuers = $getIssuers;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var CartInterface $cart */
        $cart = $value['model'];

        $method = $cart->getPayment()->getMethod();
        if (!$method) {
            return null;
        }

        $apiKey = $this->mollieHelper->getApiKey();

        try {
            $mollieApi = $this->mollieModel->loadMollieApi($apiKey);
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }

        $issuers = $this->getIssuers->execute($mollieApi, $method, 'radio');

        $output = [];
        foreach ($issuers as $issuer) {
            $issuer = (array)$issuer;
            $issuer['image'] = (array)$issuer['image'];

            $output[] = [
                'name' => $issuer['name'],
                'code' => $issuer['id'],
                'image' => $issuer['image']['size2x'],
                'svg' => $issuer['image']['svg'],
            ];
        }

        return $output;
    }
}
