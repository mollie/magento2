<?php

namespace Mollie\Payment\Plugin\Framework\App\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class CsrfValidatorSkip
{
    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        UrlInterface $url
    ) {
        $this->url = $url;
    }

    public function aroundValidate(
        CsrfValidator $subject,
        \Closure $proceed,
        RequestInterface $request,
        ActionInterface $action
    ) {
        if (strpos($this->url->getCurrentUrl(), 'mollie/checkout/webhook') !== false) {
            return null;
        }

        return $proceed($request, $action);
    }
}
