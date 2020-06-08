<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api;

interface PaymentTokenRepositoryInterface
{
    /**
     * Save PaymentToken
     * @param \Mollie\Payment\Api\Data\PaymentTokenInterface $paymentToken
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Mollie\Payment\Api\Data\PaymentTokenInterface $paymentToken
    );

    /**
     * Retrieve PaymentToken
     * @param string $id
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Retrieve PaymentToken by token
     * @param string $token
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface|null
     */
    public function getByToken($token);

    /**
     * Retrieve PaymentToken by order
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByOrder(\Magento\Sales\Api\Data\OrderInterface $order);

    /**
     * Retrieve PaymentToken by cart
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByCart(\Magento\Quote\Api\Data\CartInterface $cart);

    /**
     * Retrieve PaymentToken matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete PaymentToken
     * @param \Mollie\Payment\Api\Data\PaymentTokenInterface $paymentToken
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Mollie\Payment\Api\Data\PaymentTokenInterface $paymentToken
    );

    /**
     * Delete PaymentToken by ID
     * @param string $paymenttokenId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($paymenttokenId);
}
