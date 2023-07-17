<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form;
use Mollie\Api\Resources\Terminal;
use Mollie\Payment\Service\Mollie\MollieApiClient;

/**
 * Class Pointofsale
 *
 * @package Mollie\Payment\Block\Form
 */
class Pointofsale extends Form
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::form/pointofsale.phtml';
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    public function __construct(
        Context $context,
        MollieApiClient $mollieApiClient,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->mollieApiClient = $mollieApiClient;
    }

    /**
     * @return array{
     *     id: string,
     *     brand: string,
     *     model: string,
     *     serialNumber: string|null,
     *     description: string
     * }
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function getTerminals(): array
    {
        $storeId = $this->_storeManager->getStore()->getId();

        $mollieApiClient = $this->mollieApiClient->loadByStore((int)$storeId);
        $terminals = $mollieApiClient->terminals->page();

        $output = [];
        /** @var Terminal $terminal */
        foreach ($terminals as $terminal) {
            if (!$terminal->isActive()) {
                continue;
            }

            $output[] = [
                'id' => $terminal->id,
                'brand' => $terminal->brand,
                'model' => $terminal->model,
                'serialNumber' => $terminal->serialNumber,
                'description' => $terminal->description,
            ];
        }

        return $output;
    }
}
