<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service\Magento;

use Mollie\Payment\Service\Magento\PaymentLinkUrl;

class PaymentLinkUrlFake extends PaymentLinkUrl
{
    /**
     * @var string|null
     */
    private $url = null;

    private $shouldNotBeCalled = false;

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function shouldNotBeCalled()
    {
        $this->shouldNotBeCalled = true;
    }

    public function execute(int $orderId): string
    {
        if ($this->shouldNotBeCalled === true) {
            throw new \Exception('This method should not be called');
        }

        if ($this->url !== null) {
            return $this->url;
        }

        return parent::execute($orderId);
    }
}
