<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Framework\App\Request;

use Closure;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class CsrfValidatorSkip
{
    public function __construct(
        private UrlInterface $url
    ) {}

    public function aroundValidate(
        CsrfValidator $subject,
        Closure $proceed,
        RequestInterface $request,
        ActionInterface $action,
    ) {
        if (strpos($this->url->getCurrentUrl(), 'mollie/checkout/webhook') !== false) {
            return null;
        }

        return $proceed($request, $action);
    }
}
