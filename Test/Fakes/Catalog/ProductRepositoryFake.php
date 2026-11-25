<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

class ProductRepositoryFake implements ProductRepositoryInterface
{
    /**
     * @var ProductInterface[]
     */
    private array $products = [];

    /**
     * @var int[]
     */
    private array $requestedProductIds = [];

    public function withProduct(ProductInterface $product): void
    {
        $this->products[(int) $product->getId()] = $product;
    }

    /**
     * @return int[]
     */
    public function getRequestedProductIds(): array
    {
        return $this->requestedProductIds;
    }

    public function save(ProductInterface $product, $saveOptions = false)
    {
        $this->withProduct($product);

        return $product;
    }

    public function get($sku, $editMode = false, $storeId = null, $forceReload = false)
    {
        foreach ($this->products as $product) {
            if ($product->getSku() === $sku) {
                return $product;
            }
        }

        throw NoSuchEntityException::singleField('sku', $sku);
    }

    public function getById($productId, $editMode = false, $storeId = null, $forceReload = false)
    {
        $this->requestedProductIds[] = (int) $productId;

        if (!array_key_exists((int) $productId, $this->products)) {
            throw NoSuchEntityException::singleField('id', $productId);
        }

        return $this->products[(int) $productId];
    }

    public function delete(ProductInterface $product)
    {
        unset($this->products[(int) $product->getId()]);

        return true;
    }

    public function deleteById($sku)
    {
        return $this->delete($this->get($sku));
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        throw new StateException(__('The product repository fake does not support searching.'));
    }
}
