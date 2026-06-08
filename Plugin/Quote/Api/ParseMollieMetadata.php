<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Quote\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Quote\Api\Data\CartInterface;

class ParseMollieMetadata
{
    public function __construct(
        private ObjectFactory $objectFactory
    ) {}

    public function beforeAddProduct(
        CartInterface $subject,
        ProductInterface $product,
        $requestInfo = null,
        $processMode = AbstractType::PROCESS_MODE_FULL,
    ): array {
        $request = $this->getRequest($requestInfo);

        if (
            !$request->hasData('purchase') ||
            $request->getData('purchase') != 'subscription' ||
            !$request->hasData('recurring_metadata')
        ) {
            return [$product, $requestInfo, $processMode];
        }

        $request->setData('mollie_metadata', [
            'is_recurring' => true,
            'recurring_metadata' => $request->getData('recurring_metadata'),
        ]);

        return [$product, $request, $processMode];
    }

    private function getRequest($request)
    {
        if ($request instanceof DataObject) {
            return $request;
        }

        if ($request === null) {
            $request = 1;
        }

        if (is_numeric($request)) {
            $request = $this->objectFactory->create(['qty' => $request]);
        }

        return $request;
    }
}
