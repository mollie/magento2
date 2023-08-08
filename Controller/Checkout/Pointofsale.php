<?php

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

class Pointofsale implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        PageFactory $resultPageFactory,
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $token = $this->request->getParam('token');
        if (!$token) {
            throw new AuthorizationException(__('Invalid token'));
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Please finish the payment on the terminal.'));
        $block = $resultPage->getLayout()->getBlock('mollie.pointofsale.wait');
        $block->setData('token', $token);
        $block->setData('storeCode', $this->storeManager->getStore()->getCode());

        return $resultPage;
    }
}
