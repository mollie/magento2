<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Account;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Service\Mollie\GetCustomerMandates;

class SavedCards extends Template
{
    protected $_template = 'Mollie_Payment::account/saved_cards.phtml';

    private const CARD_LABEL_SLUG_MAP = [
        'Visa'              => 'visa',
        'Mastercard'        => 'mastercard',
        'American Express'  => 'amex',
        'Maestro'           => 'maestro',
        'Carte Bancaire'    => 'cartebancaire',
        'V PAY'             => 'vpay',
    ];

    public function __construct(
        Context $context,
        private GetCustomerMandates $getCustomerMandates,
        private FormKey $formKey,
        private CustomerSession $customerSession,
        private MollieLogger $logger,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function getMandates(): array
    {
        $customerId = (int)$this->customerSession->getCustomerId();
        if (!$customerId) {
            return [];
        }

        try {
            return $this->getCustomerMandates->execute($customerId, storeId($this->_storeManager->getStore()->getId()));
        } catch (LocalizedException $e) {
            $this->logger->addErrorLog('GetCustomerMandates', $e->getMessage());
            return [];
        }
    }

    public function getCardLogoUrl(string $cardLabel): string
    {
        $slug = self::CARD_LABEL_SLUG_MAP[$cardLabel] ?? null;
        if (!$slug) {
            return '';
        }
        return $this->getViewFileUrl('Mollie_Payment::images/cards/' . $slug . '.svg');
    }

    public function getDeleteUrl(): string
    {
        return $this->getUrl('mollie/savedcards/delete');
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
