<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Multishipping;

class CheckoutUrl
{
    /**
     * @var null|string
     */
    private $url = null;

    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }
}
