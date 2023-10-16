<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class TestApplePayDomainValidationFile extends AbstractSelfTest
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    public function execute(): void
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $parts = \parse_url($baseUrl);

        $url = $parts['scheme'] . '://' . $parts['host'] . '/.well-known/apple-developer-merchantid-domain-association';

        try {
            $contents = file_get_contents($url);
        } catch (\Exception $exception) {
            $message = __('Error: The Apple Pay domain validation file could not be accessed.');
            $this->addMessage('error', $message);
            return;
        }

        if ($contents === false) {
            $message = __('Error: The Apple Pay domain validation file could not be accessed.');
            $this->addMessage('error', $message);
            return;
        }

        if (!$contents) {
            $message = __('Error: The Apple Pay domain validation file is empty.');
            $this->addMessage('error', $message);
            return;
        }

        $mollieFile = file_get_contents('http://www.mollie.com/.well-known/apple-developer-merchantid-domain-association');
        if (!$mollieFile) {
            $message = __('Error: Unable to retrieve the Apple Pay domain validation file from Mollie.');
            $this->addMessage('error', $message);
            return;
        }

        if ($mollieFile != $contents) {
            $message = __('Error: The Apple Pay domain validation file is found but is not correct.');
            $this->addMessage('error', $message);
            return;
        }

        $message = __('Success: The Apple Pay domain validation file is found and is correct.');
        $this->addMessage('success', $message);
    }
}
