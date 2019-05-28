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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param OutputInterface $output
     *
     * @return void
     */
    public function generateFakeData(OutputInterface $output): void
    {
        $customers = $this->customerCollectionFactory->create();
        $customers->addFieldToFilter('website_id', ['in' => $this->getStoreConfig('faker/global/website_ids')]);

        $progressBar = new ProgressBar(
            $output->section(), $customers->getSize()
        );
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );
        $progressBar->start();
        $progressBar->setMessage('Customer addresses ...');
        $progressBar->display();

        foreach ($customers as $key => $customer) {
            $customerId         = $customer->getId();
            $storeId            = $customer->getStoreId();
            $minAddressNumber   = $this->getStoreConfig('faker/customer/min_address_number', $storeId);
            $maxAddressNumber   = $this->getStoreConfig('faker/customer/max_address_number', $storeId);
            $availableCountryId = explode(',', $this->getStoreConfig('general/country/allow', $storeId));
            $country            = $availableCountryId[array_rand($availableCountryId)];
            $availableRegionId  = $this->getAvailableRegionIds($country);
            $faker              = $this->getFaker($storeId);
            $iterationNumber    = $faker->numberBetween($minAddressNumber, $maxAddressNumber);

            for ($i = 0; $i < $iterationNumber; $i++) {
                $address = $this->addressDataFactory->create();
                $address->setFirstname($faker->firstName)
                    ->setLastname($faker->lastName)
                    ->setCountryId($country)
                    ->setCity($faker->city)
                    ->setPostcode($faker->postcode)
                    ->setCustomerId($customerId)
                    ->setStreet([$faker->streetAddress])
                    ->setTelephone($faker->phoneNumber);
                if (!empty($availableRegionId)) {
                    $address->setRegionId($availableRegionId[array_rand($availableRegionId)]);
                }

                $this->addressRepository->save($address);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
    }

    /**
     * @param $countryId
     *
     * @return int[]
     */
    private function getAvailableRegionIds($countryId): array
    {
        if (!isset($this->cachedRegionIds[$countryId])) {
            /** @var Collection $regionIds */
            $regionIds = $this->regionCollectionFactory->create();
            $regionIds = $regionIds->addCountryFilter($countryId)->getAllIds();

            $this->cachedRegionIds[$countryId] = $regionIds;
        }

        return $this->cachedRegionIds[$countryId];
    }
}
