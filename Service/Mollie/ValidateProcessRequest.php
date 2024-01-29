<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AuthorizationException;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;

class ValidateProcessRequest
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    public function __construct(
        RequestInterface $request,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->request = $request;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * @return array
     * @throws AuthorizationException
     */
    public function execute(): array
    {
        $orderIds = $this->getOrderIds();
        $paymentTokens = $this->getPaymentTokens();

        if (count($orderIds) !== count($paymentTokens)) {
            throw new AuthorizationException(__('Invalid payment token'));
        }

        /** @var SearchCriteriaBuilder $criteria */
        $criteria = $this->searchCriteriaBuilderFactory->create();
        $criteria->addFilter('token', $paymentTokens, 'in');

        $output = [];
        $validOrderIds = [];
        $models = $this->paymentTokenRepository->getList($criteria->create())->getItems();

        /** @var PaymentTokenInterface $model */
        foreach ($models as $model) {
            $validOrderIds[] = $model->getOrderId();
            $output[$model->getOrderId()] = $model->getToken();
        }

        sort($orderIds, SORT_NUMERIC);
        sort($validOrderIds, SORT_NUMERIC);

        if ($orderIds !== $validOrderIds) {
            throw new AuthorizationException(__('Invalid payment token'));
        }

        return $output;
    }

    private function getPaymentTokens(): array
    {
        if ($this->request->getParam('payment_token')) {
            return [$this->request->getParam('payment_token')];
        }

        return $this->request->getParam('payment_tokens', []);
    }

    private function getOrderIds(): array
    {
        if ($orderId = $this->request->getParam('order_id')) {
            return [$orderId];
        }

        return $this->request->getParam('order_ids') ?? [];
    }
}
