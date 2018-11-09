<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Ui\Component\Listing\Column;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Class PaymentMethod
 *
 * @package Mollie\Payment\Ui\Component\Listing\Column
 */
class PaymentMethod extends Column
{

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * PaymentMethod constructor.
     *
     * @param ContextInterface         $context
     * @param UiComponentFactory       $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder    $criteria
     * @param array                    $components
     * @param array                    $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteria,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteria = $criteria;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $order = $this->orderRepository->get($item["entity_id"]);
                $item[$this->getData('name')] = $order->getPayment()->getMethod();
            }
        }

        return $dataSource;
    }
}
