<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class CustomerAddress extends AbstractFaker implements FakerInterface
{
    /**
     * @var CustomerCollectionFactory $customerCollectionFactory
     */
    protected $customerCollectionFactory;
    /**
     * @var AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;
    /**
     * @var AddressInterfaceFactory $addressDataFactory
     */
    protected $addressDataFactory;
    /**
     * @var RegionCollectionFactory $regionCollectionFactory
     */
    protected $regionCollectionFactory;
    /**
     * @var string[] $cachedRegionIds
     */
    private $cachedRegionIds = [];

    /**
     * CustomerAddress constructor.
     *
     * @param ScopeConfigInterface       $scopeConfig
     * @param StoreCollectionFactory     $storeCollectionFactory
     * @param CustomerCollectionFactory  $customerCollectionFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory    $addressDataFactory
     * @param RegionCollectionFactory    $regionCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreCollectionFactory $storeCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        RegionCollectionFactory $regionCollectionFactory
    ) {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->addressRepository         = $addressRepository;
        $this->addressDataFactory        = $addressDataFactory;
        $this->regionCollectionFactory   = $regionCollectionFactory;
    }

    /**
     * @return void
     */
    public function generateFakeData(): void
    {
        $customers = $this->customerCollectionFactory->create();
        $customers->addFieldToFilter('website_id', ['in' => $this->getStoreConfig('faker/global/website_ids')]);

        foreach ($customers as $key => $customer) {
            $customerId         = $customer->getId();
            $storeId            = $customer->getStoreId();
            $minAddressNumber   = $this->getStoreConfig('faker/customer/min_address_number', $storeId);
            $maxAddressNumber   = $this->getStoreConfig('faker/customer/max_address_number', $storeId);
            $availableCountryId = $this->getStoreConfig('general/country/allow', $storeId);
            $availableRegionId  = $this->getAvailableRegionIds($storeId);
            $faker              = $this->getFaker($storeId);
            $iterationNumber    = $faker->numberBetween($minAddressNumber, $maxAddressNumber);

            for ($i = 0; $i < $iterationNumber; $i++) {
                $address = $this->addressDataFactory->create();
                $address->setFirstname($faker->firstName)
                    ->setLastname($faker->lastName)
                    ->setCountryId(
                        array_rand($availableCountryId)
                    )
                    ->setRegionId(array_rand($availableRegionId))
                    ->setCity($faker->city)
                    ->setPostcode($faker->postcode)
                    ->setCustomerId($customerId)
                    ->setStreet([$faker->streetAddress])
                    ->setTelephone($faker->phoneNumber);

                $this->addressRepository->save($address);
            }
        }
    }

    /**
     * @param $storeId
     *
     * @return int[]
     */
    private function getAvailableRegionIds($storeId): array
    {
        if (!isset($this->cachedRegionIds[$storeId])) {
            /** @var Collection $regionIds */
            $regionIds = $this->regionCollectionFactory->create();
            $regionIds->addAllowedCountriesFilter($storeId)->getAllIds();

            $this->cachedRegionIds[$storeId] = $regionIds;
        }

        return $this->cachedRegionIds[$storeId];
    }
}
