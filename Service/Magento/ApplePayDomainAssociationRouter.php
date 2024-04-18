<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;

class ApplePayDomainAssociationRouter implements RouterInterface
{
    /**
     * @var ActionFactory
     */
    private $actionFactory;
    /**
     * @var ActionList
     */
    private $actionList;
    /**
     * @var ConfigInterface
     */
    private $routeConfig;

    public function __construct(
        ActionFactory $actionFactory,
        ActionList $actionList,
        ConfigInterface $routeConfig
    ) {
        $this->actionFactory = $actionFactory;
        $this->actionList = $actionList;
        $this->routeConfig = $routeConfig;
    }

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
