<?php

namespace Mollie\Payment\Model\Adminhtml\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Api\ApiKeyFallbackRepositoryInterface;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterfaceFactory;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterface;

class SaveApiKey extends Encrypted
{
    /**
     * @var ApiKeyFallbackRepositoryInterface
     */
    private $apiKeyFallbackRepository;

    /**
     * @var ApiKeyFallbackInterfaceFactory
     */
    private $apiKeyFallbackFactory;
    /**
     * @var UpdateProfileId
     */
    private $updateProfileId;

    /**
     * @var bool|string
     */
    private $shouldUpdateProfileId = false;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor,
        ApiKeyFallbackRepositoryInterface $apiKeyFallbackRepository,
        ApiKeyFallbackInterfaceFactory $apiKeyFallbackFactory,
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
            $encryptor,
            $resource,
            $resourceCollection,
            $data
        );

        $this->apiKeyFallbackRepository = $apiKeyFallbackRepository;
        $this->apiKeyFallbackFactory = $apiKeyFallbackFactory;
        $this->updateProfileId = $updateProfileId;
    }

    public function beforeSave()
    {
        // Save the unencrypted value so we can test it.
        $value = (string)$this->getValue();

        parent::beforeSave();

        if ($this->getValue() !== '******' && $this->getOldValue() != $value) {
            // Validate the new API key before saving.
            (new MollieApiClient())->setApiKey($value);

            $this->shouldUpdateProfileId = $value;

            $this->saveApiKey();
            $this->_cacheManager->clean(['mollie_payment', 'mollie_payment_methods']);
        }

        return $this;
    }

    public function afterSave()
    {
        if ($this->shouldUpdateProfileId !== false) {
            $this->updateProfileId->execute($this->shouldUpdateProfileId, $this->getScope(), $this->getScopeId());
        }

        return parent::afterSave();
    }

    private function saveApiKey(): void
    {
        /** @var ApiKeyFallbackInterface $model */
        $model = $this->apiKeyFallbackFactory->create();
        $model->setApikey($this->_encryptor->encrypt($this->getOldValue()));
        $model->setMode($this->getPath() === 'payment/mollie_general/apikey_live' ? 'live' : 'test');

        $this->apiKeyFallbackRepository->save($model);
    }
}
