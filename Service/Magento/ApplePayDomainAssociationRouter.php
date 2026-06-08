<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;

class ApplePayDomainAssociationRouter implements RouterInterface
{
    public function __construct(
        private ActionFactory $actionFactory,
        private ActionList $actionList,
        private ConfigInterface $routeConfig
    ) {}

    public function match(RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');
        if ($identifier !== '.well-known/apple-developer-merchantid-domain-association') {
            return null;
        }

        $modules = $this->routeConfig->getModulesByFrontName('mollie');
        if (empty($modules)) {
            return null;
        }

        $actionClassName = $this->actionList->get($modules[0], null, 'ApplePay', 'AppleDeveloperMerchantidDomainAssociation');

        return $this->actionFactory->create($actionClassName);
    }
}
