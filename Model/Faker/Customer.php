<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Customer\Api\Data\CustomerInterface;
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
     * @var CustomerFactory $customerFactory
     */
    protected $customerFactory;
    /**
     * @var CustomerResourceModel $customerResourceModel
     */
    protected $customerResourceModel;
    /**
     * @var CustomerGroupCollectionFactory $customerGroupCollectionFactory
     */
    protected $customerGroupCollectionFactory;

    /**
     * Customer constructor.
     *
     * @param ScopeConfigInterface           $scopeConfig
     * @param StoreCollectionFactory         $storeCollectionFactory
     * @param CustomerFactory                $customerFactory
     * @param CustomerResourceModel          $customerResourceModel
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreCollectionFactory $storeCollectionFactory,
        CustomerFactory $customerFactory,
        CustomerResourceModel $customerResourceModel,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory
    ) {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->customerFactory                = $customerFactory;
        $this->customerResourceModel          = $customerResourceModel;
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
            $faker     = $this->getFaker($store);
            $websiteId = $store->getWebsiteId();
            $storeId   = $store->getStoreId();
            for ($i = 0; $i < $this->getStoreConfig('faker/customer/number', $storeId); $i++) {
                /** @var CustomerModel $customer */
                $customer = $this->customerFactory->create();

                $customer->setData(
                    [
                        CustomerInterface::PREFIX     => $faker->title,
                        CustomerInterface::FIRSTNAME  => $faker->firstName,
                        CustomerInterface::LASTNAME   => $faker->lastName,
                        CustomerInterface::EMAIL      => $faker->email,
                        CustomerInterface::DOB        => $faker->date('m/d/Y'),
                        CustomerInterface::GENDER     => $faker->numberBetween(0, 1),
                        CustomerInterface::GROUP_ID   => array_rand($customerGroupIds),
                        CustomerInterface::STORE_ID   => $storeId,
                        CustomerInterface::WEBSITE_ID => $websiteId,
                        'password'                    => $faker->password,
                    ]
                );

                $this->customerResourceModel->save($customer);
            }
        }
    }
}
