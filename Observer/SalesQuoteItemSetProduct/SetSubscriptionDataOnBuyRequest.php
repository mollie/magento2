<?php

namespace Mollie\Payment\Observer\SalesQuoteItemSetProduct;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartItemInterface;

class SetSubscriptionDataOnBuyRequest implements ObserverInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    public function execute(Observer $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getData('product');
        if (!$product->getData('mollie_subscription_product')) {
            return;
        }

        $table = $product->getData('mollie_subscription_table');
        if (!$table) {
            return;
        }

        $buyRequest = $this->getBuyRequest($observer->getData('quote_item'));
        $value = $this->serializer->unserialize($buyRequest->getValue());
        if (isset($value['mollie_metadata'])) {
            return;
        }

        $data = $this->serializer->unserialize($table);
        $default = $this->getDefault($data);

        $value['mollie_metadata'] = [
            'purchase' => 'subscription',
            'recurring_metadata' => [
                'option_id' => $default['identifier'],
            ],
        ];

        $buyRequest->setValue($this->serializer->serialize($value));
    }

    private function getDefault(array $data): array
    {
        foreach ($data as $row) {
            if (isset($row['isDefault']) && $row['isDefault']) {
                return $row;
            }
        }

        return array_shift($data);
    }

    private function getBuyRequest(CartItemInterface $item): OptionInterface
    {
        /** @var OptionInterface[] $options */
        $options = $item->getOptions();
        foreach ($options as $option) {
            if ($option->getCode() == 'info_buyRequest') {
                return $option;
            }
        }

        throw new NotFoundException(__('No info_buyRequest option found'));
    }
}
