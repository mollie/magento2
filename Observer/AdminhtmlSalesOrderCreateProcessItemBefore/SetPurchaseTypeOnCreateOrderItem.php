<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\AdminhtmlSalesOrderCreateProcessItemBefore;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SetPurchaseTypeOnCreateOrderItem implements ObserverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    public function execute(Observer $observer)
    {
        /** @var RequestInterface $request */
        $request = $observer->getData('request_model');

        if ($request->has('item') && !$request->getPost('update_items')
        ) {
            $itemsChanged = false;
            $items = $request->getPost('item');
            foreach ($items as $item => $requestData) {
                if (!$this->productAllowsOneTimePurchase($item)) {
                    continue;
                }

                $itemsChanged = true;
                $items[$item]['purchase'] = 'onetime';
            }

            if ($itemsChanged) {
                $request->setPostValue('item', $items);
            }
        }
    }

    private function productAllowsOneTimePurchase(int $productId): bool
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return !!$product->getData('mollie_subscription_product');
    }
}
