<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Ui\DataProvider\PaymentReminder\Listing\Column;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PendingActions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['entity_id'])) {
                continue;
            }

            $name = $this->getData('name');
            $item[$name]['delete']   = [
                'href'  => $this->urlBuilder->getUrl(
                    'mollie/reminder/deletePending',
                    ['id' => $item['entity_id']]
                ),
                'label' => __('Delete')
            ];

            $item[$name]['send_now']   = [
                'href'  => $this->urlBuilder->getUrl(
                    'mollie/reminder/sendNow',
                    ['id' => $item['entity_id']]
                ),
                'label' => __('Send now')
            ];
        }

        return $dataSource;
    }
}