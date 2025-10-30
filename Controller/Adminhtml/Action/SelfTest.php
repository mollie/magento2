<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Service\Mollie\SelfTests\AbstractSelfTest;

class SelfTest extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Mollie_Payment::config';

    public function __construct(
        Context $context,
        private JsonFactory $resultJsonFactory,
        private MollieHelper $mollieHelper,
        /**
         * @var AbstractSelfTest[]
         */
        private array $tests,
    ) {
        parent::__construct($context);
    }

    /**
     * Admin controller for compatibility test
     *
     * - Check if Mollie php API is installed
     * - Check for minimum system requirements of API
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();
        if (!class_exists('Mollie\Api\CompatibilityChecker')) {
            return $this->getPhpApiErrorMessage($result);
        }

        $messages = [];
        foreach ($this->tests as $test) {
            $test->execute();

            $messages = array_merge($messages, $test->getMessages());
        }

        $output = '';
        foreach ($messages as $message) {
            $output .= '<div class="mollie-' . $message['type'] . '">' . $message['message'] . '</div>';
        }

        $result->setData(['success' => true, 'msg' => $output]);

        return $result;
    }

    private function getPhpApiErrorMessage(Json $result): Json
    {
        $results = ['<span class="mollie-error">' . $this->mollieHelper->getPhpApiErrorMessage() . '</span>'];

        if (stripos(__DIR__, 'app/code') !== false) {
            $msg = __('Warning: We recommend to install the Mollie extension using Composer, currently it\'s installed in the app/code folder.');
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        }

        $result->setData(['success' => true, 'msg' => implode('<br/>', $results)]);

        return $result;
    }
}
