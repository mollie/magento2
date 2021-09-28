<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Customer;

use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractTokenRenderer;
use Mollie\Payment\Model\Methods\Creditcard;

class CardRenderer extends AbstractTokenRenderer
{
    /**
     * @var Repository
     */
    private $assetRepository;

    public function __construct(
        Template\Context $context,
        Repository $assetRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->assetRepository = $assetRepository;
    }

    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === Creditcard::CODE;
    }

    /**
     * @return string
     */
    public function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    /**
     * @return string
     */
    public function getExpDate()
    {
        return __('Unknown');
    }

    /**
     * @return string
     */
    public function getIconUrl()
    {
        $type = $this->getTokenDetails()['type'];
        $asset = $this->assetRepository->getUrl('Mollie_Payment::images/creditcard-issuers/' . $type . '.svg');

        return $asset;
    }

    public function getIconHeight()
    {
        return null;
    }

    public function getIconWidth()
    {
        return null;
    }
}
