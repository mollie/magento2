<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderFactory;
use Magento\Framework\Data\OptionSourceInterface;

class ProductAttributes implements OptionSourceInterface
{
    public function __construct(
        private AttributeRepositoryInterface $repository,
        private SearchCriteriaBuilder $builder,
        private SortOrderFactory $sortOrderFactory
    ) {}

    public function toOptionArray(): array
    {
        /** @var SortOrder $sortOrder */
        $sortOrder = $this->sortOrderFactory->create();
        $sortOrder->setField('frontend_label');
        $sortOrder->setDirection(SortOrder::SORT_ASC);

        $this->builder->addSortOrder($sortOrder);

        $result = $this->repository->getList(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $this->builder->create(),
        );

        $output = [];
        $output[] = ['value' => '', 'label' => __('Please select')];

        foreach ($result->getItems() as $item) {
            $output[] = [
                'value' => $item->getAttributeCode(),
                'label' => $item->getDefaultFrontendLabel(),
            ];
        }

        return $output;
    }
}
