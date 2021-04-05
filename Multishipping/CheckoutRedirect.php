<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Multishipping;

use Magento\Framework\ObjectManagerInterface;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;

class CheckoutRedirect
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $redirect;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    private $response;

    public function __construct(
        ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\ResponseInterface $response
    ) {
        $this->objectManager = $objectManager;
        $this->redirect = $redirect;
        $this->response = $response;
    }

    /**
     * Normally it is forbidden to use the object manager, but the use case here is justified: This code is used in
     * one the most critical parts of this extension: When a user has paid and is redirected from Mollie back to the
     * webshop. The Multishipping module is rarely used and often replaced. So we are only invoking the multishipping
     * code on the moment we really need this.
     */
    public function redirect()
    {
        $state = $this->objectManager->create(State::class);
        $state->setCompleteStep(State::STEP_OVERVIEW);
        $state->setActiveStep(State::STEP_SUCCESS);

        $this->redirect->redirect($this->response, 'multishipping/checkout/success?utm_nooverride=1');
    }
}
