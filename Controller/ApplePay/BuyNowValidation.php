<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\ApplePay;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Controller\Action;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class BuyNowValidation extends Action
{
    /**
     * @var GuestCartManagementInterface
     */
    private $cartManagement;

    /**
     * @var GuestCartRepositoryInterface
     */
    private $guestCartRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    public function __construct(
        Context $context,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        Config $config,
        ResolverInterface $resolver,
        Validator $formKeyValidator,
        GuestCartManagementInterface $cartManagement,
        GuestCartRepositoryInterface $guestCartRepository,
        CartRepositoryInterface $cartRepository,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        MollieApiClient $mollieApiClient,
        UrlInterface $url
    ) {
        parent::__construct($context, $customerSession, $customerRepository, $accountManagement);

        $this->config = $config;
        $this->resolver = $resolver;
        $this->formKeyValidator = $formKeyValidator;
        $this->cartManagement = $cartManagement;
        $this->guestCartRepository = $guestCartRepository;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->url = $url;
        $this->mollieApiClient = $mollieApiClient;
    }

    /**
     * Initialize product instance from request data
     *
     * @return ProductInterface|false
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $cartId = $this->cartManagement->createEmptyCart();
        $cart = $this->guestCartRepository->get($cartId);

        $params = $this->getRequest()->getParams();

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            if (isset($params['qty'])) {
                $filter = new LocalizedToNormalized(['locale' => $this->resolver->getLocale()]);
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                $response->setHttpResponseCode(404);
                return $response->setData([
                    'error' => true,
                    'message' => __('Product not found')
                ]);
            }

            $cart->addProduct($product, new \Magento\Framework\DataObject($params));
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $this->cartRepository->save($cart);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );

            $this->config->addToLog('error', $e);

            $response->setHttpResponseCode(403);
            return $response->setData([
                'error' => true,
                'message' => __('We can\'t add this item to your shopping cart right now.')
            ]);
        }

        try {
            $store = $this->storeManager->getStore();
            $api = $this->mollieApiClient->loadByApiKey($this->getLiveApiKey((int)$store->getId()));
            $url = $this->url->getBaseUrl();

            $result = $api->wallets->requestApplePayPaymentSession(
                parse_url($url, PHP_URL_HOST),
                $this->getRequest()->getParam('validationURL')
            );
        } catch (\Exception $exception) {
            $response->setHttpResponseCode(500);
            $response->setData([
                'error' => true,
                'message' => $exception->getMessage(),
            ]);

            return $response;
        }

        $response->setData([
            'cartId' => $cartId,
            'store_name' => $this->storeManager->getStore()->getName(),
            'amount' => $cart->getGrandTotal(),
            'validationData' => json_decode($result),
        ]);

        return $response;
    }

    private function getLiveApiKey(int $storeId): string
    {
        $liveApikey = $this->config->getLiveApiKey($storeId);
        if (!$liveApikey) {
            throw new \Exception(__('For Apple Pay the live API key is required, even when in test mode'));
        }

        return $liveApikey;
    }
}
