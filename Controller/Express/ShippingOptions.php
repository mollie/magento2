<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Express;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestContentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\Express\ProductPaymentMetadata;
use Mollie\Payment\Service\Mollie\Express\ProductQuote;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Express\ShippingOptionsForAddress;

/**
 * Mollie calls this endpoint when the customer picks an address in the Express Component, and shows the returned
 * shipping options. See https://docs.mollie.com/docs/handle-shipping-options-with-express-component
 */
class ShippingOptions implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const SESSION_ID_KEY = 'mollie_session_id';
    private const STATUS_FORBIDDEN = 403;
    private const STATUS_UNSUPPORTED_ADDRESS = 422;
    private const UNSUPPORTED_ADDRESS_TYPE = 'https://docs.mollie.com/reference/errors/unsupported-address';
    private const LOGGED_ADDRESS_FIELDS = ['country', 'region', 'postalCode', 'city'];
    private const LOGGED_FIELD_MAX_LENGTH = 64;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly ShippingOptionsForAddress $shippingOptionsForAddress,
        private readonly Config $config,
        private readonly MollieApiClient $mollieApiClient,
        private readonly ProductQuote $productQuote,
        private readonly StoreManagerInterface $storeManager,
    ) {}

    public function execute(): Json
    {
        $body = $this->getBody();
        $cart = $this->getCart($body);

        if ($cart === null) {
            return $this->jsonFactory->create()->setHttpResponseCode(self::STATUS_FORBIDDEN)->setData([]);
        }

        $address = is_array($body['shippingAddress'] ?? null) ? $body['shippingAddress'] : [];
        $options = $this->shippingOptionsForAddress->execute($cart, $address);

        if ($options === []) {
            return $this->unsupportedAddressResponse($cart, $address);
        }

        return $this->jsonFactory->create()->setData(['options' => $options]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function getBody(): array
    {
        if (!$this->request instanceof RequestContentInterface) {
            return [];
        }

        $body = json_decode((string)$this->request->getContent(), true);

        return is_array($body) ? $body : [];
    }

    /**
     * @param array<string, mixed> $body
     */
    private function getCart(array $body): ?CartInterface
    {
        $sessionId = $body['sessionId'] ?? null;
        if (!is_string($sessionId) || $sessionId === '') {
            return null;
        }

        $quoteId = (int)$this->request->getParam('quoteId');
        if ($quoteId === 0) {
            return $this->getProductCart($sessionId);
        }

        $cart = $this->cartRepository->get($quoteId);

        return $this->isSessionOfCart($cart, $sessionId) ? $cart : null;
    }

    /**
     * The quote id is in the URL, so only answer for the session that was created for this cart.
     */
    private function isSessionOfCart(CartInterface $cart, string $sessionId): bool
    {
        $expected = (string)$cart->getPayment()->getAdditionalInformation(self::SESSION_ID_KEY);

        return $expected !== '' && hash_equals($expected, $sessionId);
    }

    /**
     * A product page session has no saved quote. Its metadata, fetched from Mollie with our own API key, holds
     * what is needed to build it again.
     */
    private function getProductCart(string $sessionId): ?CartInterface
    {
        try {
            $session = $this->mollieApiClient->loadByStore($this->getStoreId())->sessions->get($sessionId);
        } catch (ApiException $exception) {
            return null;
        }

        if (!ProductPaymentMetadata::isProductPayment($session->metadata)) {
            return null;
        }

        $metadata = ProductPaymentMetadata::fromMollie($session->metadata);

        return $this->productQuote->create($metadata->buyRequest, $metadata->storeId, $metadata->customerId);
    }

    /**
     * Store views that share a domain without store codes in their URLs all resolve to the same store, so the
     * callback URL carries the store id of the session.
     */
    private function getStoreId(): int
    {
        $storeId = (int)$this->request->getParam('storeId');
        if ($storeId !== 0) {
            return $storeId;
        }

        return (int)$this->storeManager->getStore()->getId();
    }

    /**
     * @param array<string, mixed> $address
     */
    private function unsupportedAddressResponse(CartInterface $cart, array $address): Json
    {
        $detail = 'No shipping methods are available for this address.';

        $this->config->addToLog('info', [
            'message' => 'Express Components: ' . $detail,
            'quoteId' => $cart->getId(),
            'address' => $this->loggableAddress($address),
        ]);

        return $this->jsonFactory->create()
            ->setHttpResponseCode(self::STATUS_UNSUPPORTED_ADDRESS)
            ->setData([
                'type' => self::UNSUPPORTED_ADDRESS_TYPE,
                'status' => self::STATUS_UNSUPPORTED_ADDRESS,
                'detail' => $detail,
            ]);
    }

    /**
     * The address comes from an unauthenticated request, so only the fields the rate calculation uses are logged,
     * capped in length.
     *
     * @param array<string, mixed> $address
     * @return array<string, string>
     */
    private function loggableAddress(array $address): array
    {
        return array_map(
            static fn (mixed $value): string => mb_substr(
                is_scalar($value) ? (string)$value : '',
                0,
                self::LOGGED_FIELD_MAX_LENGTH,
            ),
            array_intersect_key($address, array_flip(self::LOGGED_ADDRESS_FIELDS)),
        );
    }
}
