<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Mollie\Payment\Config;

class ChangeApiMode extends Value
{
    /**
     * @var Config
     */
    private $mollieConfig;
    /**
     * @var FlushMollieCache
     */
    private $flushMollieCache;
    /**
     * @var UpdateProfileId
     */
    private $updateProfileId;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Config $mollieConfig,
        FlushMollieCache $flushMollieCache,
        UpdateProfileId $updateProfileId,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );

        $this->mollieConfig = $mollieConfig;
        $this->flushMollieCache = $flushMollieCache;
        $this->updateProfileId = $updateProfileId;
    }

    public function beforeSave(): self
    {
        $this->flushMollieCache->flush();

        return parent::beforeSave();
    }

    public function afterSave()
    {
        $apiKey = $this->getApiKey($this->getValue());
        if ($apiKey) {
            $this->updateProfileId->execute($apiKey, $this->getScope(), (int)$this->getScopeId());
        }

        return parent::afterSave();
    }

    private function getApiKey(string $mode): string
    {
        if ($mode === 'live') {
            return $this->mollieConfig->getLiveApiKey((int)$this->getScopeId());
        }

        return $this->mollieConfig->getTestApiKey((int)$this->getScopeId());
    }
}
