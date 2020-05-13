<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderFactory;
use Magento\Framework\Data\OptionSourceInterface;

class ProductAttributes implements OptionSourceInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $repository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $builder;

    /**
     * @var SortOrderFactory
     */
    private $sortOrderFactory;

    public function __construct(
        AttributeRepositoryInterface $repository,
        SearchCriteriaBuilder $builder,
        SortOrderFactory $sortOrderFactory
    ) {
        $this->repository = $repository;
        $this->builder = $builder;
        $this->sortOrderFactory = $sortOrderFactory;
    }

    public function toOptionArray()
    {
        /** @var SortOrder $sortOrder */
        $sortOrder = $this->sortOrderFactory->create();
        $sortOrder->setField('frontend_label');
        $sortOrder->setDirection(SortOrder::SORT_ASC);

        $this->builder->addSortOrder($sortOrder);

        $result = $this->repository->getList(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $this->builder->create()
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