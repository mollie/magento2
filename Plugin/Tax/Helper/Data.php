<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Tax\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Helper\Data as Subject;
use Mollie\Payment\Config;

class Data
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var TaxClassRepositoryInterface
     */
    private $taxClassRepository;
    /**
     * @var array
     */
    private $result = [];

    public function __construct(
        Config $config,
        TaxClassRepositoryInterface $taxClassRepository
    ) {
        $this->config = $config;
        $this->taxClassRepository = $taxClassRepository;
    }

    /**
     * This plugin aims to add the Mollie Payment Fee tax to the tax summary when generating a creditmemo.
     * @see \Magento\Tax\Helper\Data::getCalculatedTaxes()
     *
     * @param Subject $subject
     * @param array $result
     * @param OrderInterface|InvoiceInterface|CreditmemoInterface $source
     * @return void
     * @throws NoSuchEntityException
     */
    public function afterGetCalculatedTaxes(Subject $subject, array $result, $source): array
    {
        if (!$source instanceof CreditmemoInterface && !$source instanceof InvoiceInterface) {
            return $result;
        }

        $this->result = $result;

        $order = $source->getOrder();
        if (!$order->getPayment() ||
            strstr($order->getPayment()->getMethod(), 'mollie_methods_') === false
        ) {
            return $result;
        }

        $amount = $order->getMolliePaymentFee();
        $taxAmount = $order->getMolliePaymentFeeTax();
        $baseTaxAmount = $order->getMolliePaymentFee();

        $rate = 0;
        if ((float)$order->getMolliePaymentFeeTax()) {
            $rate = round(($taxAmount / $amount) * 100);
        }

        $taxClassId = $this->config->paymentSurchargeTaxClass(
            $order->getPayment()->getMethod(),
            $order->getStoreId()
        );

        $taxClass = $this->taxClassRepository->get($taxClassId);
        $name = $taxClass->getClassName();

        $this->mergeResult([
            'title' => $name,
            'percent' => $rate,
            'tax_amount' => $taxAmount,
            'base_tax_amount' => $baseTaxAmount,
        ]);

        return $this->result;
    }

    private function mergeResult(array $taxClass): void
    {
        $existing = array_search($taxClass['title'], array_column($this->result, 'title'));

        if ($existing === false) {
            $this->result[] = [
                'title' => $taxClass['title'],
                'percent' => $taxClass['percent'],
                'tax_amount' => $taxClass['tax_amount'],
                'base_tax_amount' => $taxClass['base_tax_amount'],
            ];

            return;
        }

        $this->result[$existing]['tax_amount'] += $taxClass['tax_amount'];
        $this->result[$existing]['base_tax_amount'] += $taxClass['base_tax_amount'];
    }
}
