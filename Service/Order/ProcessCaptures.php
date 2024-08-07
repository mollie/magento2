<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterfaceFactory;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Mollie\Api\Resources\Capture;
use Mollie\Api\Resources\CaptureCollection;
use Mollie\Payment\Config;

class ProcessCaptures
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var BuilderInterfaceFactory
     */
    private $transactionBuilderFactory;
    /**
     * @var ManagerInterface
     */
    private $transactionManager;
    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    public function __construct(
        Config $config,
        BuilderInterfaceFactory $transactionBuilderFactory,
        ManagerInterface $transactionManager,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        $this->transactionBuilderFactory = $transactionBuilderFactory;
        $this->transactionManager = $transactionManager;
        $this->invoiceRepository = $invoiceRepository;
        $this->config = $config;
    }

    public function execute(OrderInterface $order, CaptureCollection $captures): void
    {
        if (!$this->config->useManualCapture($order->getStoreId())) {
            return;
        }

        foreach ($captures as $capture) {
            $this->handle($order, $capture);
        }
    }

    private function handle(OrderInterface $order, Capture $capture): void
    {
        $id = $capture->id;

        if ($this->transactionManager->isTransactionExists($id, $order->getPayment()->getId(), $order->getId())) {
            return;
        }

        $this->transactionBuilderFactory->create()
            ->setPayment($order->getPayment())
            ->setOrder($order)
            ->setTransactionId($id)
            ->setAdditionalInformation(['capture_id' => $id])
            ->setFailSafe(true)
            ->build(TransactionInterface::TYPE_CAPTURE);

        try {
            $invoice = $this->invoiceRepository->get($capture->metadata->invoice_id);
            $invoice->setState(Invoice::STATE_PAID);
            $this->invoiceRepository->save($invoice);
        } catch (NoSuchEntityException $exception) {
            // No invoice found
        }

        $order->getPayment()->registerCaptureNotification($capture->amount->value, true);
    }
}
