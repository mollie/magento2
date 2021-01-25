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
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Mollie;
use Psr\Log\LoggerInterface;

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
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * @var Mollie
     */
    private $mollie;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    public function __construct(
        Context $context,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        Validator $formKeyValidator,
        GuestCartManagementInterface $cartManagement,
        GuestCartRepositoryInterface $guestCartRepository,
        CartRepositoryInterface $cartRepository,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        MollieHelper $mollieHelper,
        Mollie $mollie,
        UrlInterface $url,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        parent::__construct($context, $customerSession, $customerRepository, $accountManagement);

        $this->formKeyValidator = $formKeyValidator;
        $this->cartManagement = $cartManagement;
        $this->guestCartRepository = $guestCartRepository;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->mollieHelper = $mollieHelper;
        $this->mollie = $mollie;
        $this->url = $url;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
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

        try {
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                return $this->goBack();
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
            $this->_objectManager->get(LoggerInterface::class)->critical($e);
            return $this->goBack();
        }

        $store = $this->storeManager->getStore();
        $api = $this->mollie->loadMollieApi($this->mollieHelper->getApiKey($store->getId()));
        $url = $this->url->getBaseUrl();

        $result = $api->wallets->requestApplePayPaymentSession(
            parse_url($url, PHP_URL_HOST),
            $this->getRequest()->getParam('validationURL')
        );

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $response->setData([
            'cartId' => $cartId,
            'store_name' => $this->storeManager->getStore()->getName(),
            'amount' => $cart->getGrandTotal(),
            'validationData' => json_decode($result),
        ]);

        return $response;
    }
}
