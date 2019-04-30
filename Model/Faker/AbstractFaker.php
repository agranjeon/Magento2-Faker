<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Faker\Factory;
use Faker\Generator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Store\Model\ResourceModel\Store\Collection;
use Magento\Store\Model\ScopeInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
abstract class AbstractFaker
{
    /**
     * Description $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;
    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;
    /**
     * @var array $cachedConfigurations
     */
    private $cachedConfigurations = [];

    /**
     * AbstractFaker constructor
     *
     * @param ScopeConfigInterface   $scopeConfig
     * @param StoreCollectionFactory $storeCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->scopeConfig            = $scopeConfig;
        $this->storeCollectionFactory = $storeCollectionFactory;
    }

    /**
     * @param string                         $path
     * @param StoreInterface|int|string|null $store
     *
     * @return mixed
     */
    protected function getStoreConfig(string $path, $store = null): ?string
    {
        if ($store instanceof StoreInterface) {
            $store = $store->getId();
        }
        $value = $this->cachedConfigurations[$path][$store] ?? null;

        if (!$value) {
            $this->cachedConfigurations[$path][$store] = $value = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORES,
                $store
            );
        }

        return $value;
    }

    /**
     * @return Collection
     */
    protected function getStores(): Collection
    {
        /** @var Collection $storeCollection */
        $storeCollection = $this->storeCollectionFactory->create();
        $storeCollection->addFieldToFilter(
            'website_id',
            ['in' => explode(',', $this->getStoreConfig('faker/global/website_ids'))]
        )->addStatusFilter(1);

        return $storeCollection;
    }

    /**
     * @param $store
     *
     * @return Generator
     */
    protected function getFaker($store): Generator
    {
        return Factory::create($this->getStoreConfig('faker/global/locale', $store));
    }
}
