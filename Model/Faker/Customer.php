<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Customer extends AbstractFaker implements FakerInterface
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var CustomerResourceModel
     */
    private $customerResourceModel;
    /**
     * @var CustomerGroupCollectionFactory
     */
    private $customerGroupCollectionFactory;

    /**
     * Customer constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param CustomerResourceModel $customerResourceModel
     * @param CustomerFactory $customerFactory
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(ScopeConfigInterface $scopeConfig, StoreCollectionFactory $storeCollectionFactory, CustomerResourceModel $customerResourceModel, CustomerFactory $customerFactory, CustomerGroupCollectionFactory $customerGroupCollectionFactory)
    {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->customerFactory = $customerFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
    }

    /**
     * @return void
     */
    public function generateFakeData(): void
    {
        /** @var int[] $customerGroupIds */
        $customerGroupIds = $this->customerGroupCollectionFactory->create()->getAllIds();

        /** @var Store $store */
        foreach ($this->getStores() as $store) {
            $faker = $this->getFaker($store);
            $websiteId = $store->getWebsiteId();
            $storeId = $store->getStoreId();
            for ($i = 0; $i < $this->getStoreConfig('faker/customer/number', $storeId); $i++) {
                /** @var CustomerModel $customer */
                $customer = $this->customerFactory->create();

                $customer->setData([
                    'prefix' => $faker->title,
                    'firstname' => $faker->firstName,
                    'lastname' => $faker->lastName,
                    'email' => $faker->email,
                    'dateOfBirth' => $faker->date('m/d/Y'),
                    'gender' => $faker->numberBetween(0, 1),
                    'group_id' => array_rand($customerGroupIds),
                    'store_id' => $storeId,
                    'website_id' => $websiteId,
                    'password' => $faker->password
                ]);

                $this->customerResourceModel->save($customer);
            }
        }
    }
}
