<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form;
use Mollie\Payment\Service\Mollie\AvailableTerminals;

/**
 * Class Pointofsale
 *
 * @package Mollie\Payment\Block\Form
 */
class Pointofsale extends Form
{
    /**
     * @var AvailableTerminals
     */
    private $availableTerminals;

    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::form/pointofsale.phtml';

    public function __construct(
        Context $context,
        AvailableTerminals $availableTerminals,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->availableTerminals = $availableTerminals;
    }

    /**
     * @return array{
     *     id: string,
     *     brand: string,
     *     model: string,
     *     serialNumber: string|null,
     *     description: string
     * }
     */
    public function getTerminals(): array
    {
        $storeId = $this->_storeManager->getStore()->getId();

        return $this->availableTerminals->execute((int)$storeId);
    }
}
