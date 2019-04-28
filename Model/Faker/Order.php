<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Order extends AbstractFaker implements FakerInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * Order constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quoteFactory
     * @param ProductFactory $productFactory
     * @param QuoteManagement $quoteManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(ScopeConfigInterface $scopeConfig, StoreCollectionFactory $storeCollectionFactory, CustomerRepositoryInterface $customerRepository, StoreManagerInterface $storeManager, QuoteFactory $quoteFactory, ProductFactory $productFactory, QuoteManagement $quoteManagement, SearchCriteriaBuilder $searchCriteriaBuilder, CollectionFactory $productCollectionFactory)
    {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->productFactory = $productFactory;
        $this->quoteManagement = $quoteManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @return void
     */
    public function generateFakeData(): void
    {
        $productIds = $this->getProductIds();
        $customers = $this->getCustomers();
        foreach ($customers as $customer) {
            $store = $this->storeManager->getStore($customer->getStoreId());
            $availableShippingMethods = $this->getStoreConfig('faker/order/shipping_method', $store);
            $availablePaymentMethods = $this->getStoreConfig('faker/order/payment_method', $store);

            $numberOfOrders = $this->getStoreConfig('faker/order/number', $store);
            for ($i=0; $i < $numberOfOrders; $i++) {
                $shippingMethod = array_rand($availableShippingMethods);
                $paymentMethod = array_rand($availablePaymentMethods);

                $quote = $this->quoteFactory->create();
                $quote->setStore($store);
                $quote->setCurrency();
                $quote->assignCustomer($customer);

                $shippingAddress = $customer->getDefaultShipping();
                $billingAddress = $customer->getDefaultBilling();
                $numberOfItems = rand($this->getStoreConfig('faker/order/min_items_number', $store), $this->getStoreConfig('faker/order/max_items_number', $store));
                //add items in quote
                for ($i = 0; $i < $numberOfItems; $i++) {
                    $product = $this->productFactory->create()->load(array_rand($productIds));
                    $quote->addProduct(
                        $product,
                        rand(1,3)
                    );
                }

                $quote->getBillingAddress()->addData($shippingAddress);
                $quote->getShippingAddress()->addData($billingAddress);

                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setCollectShippingRates(true)
                    ->collectShippingRates()
                    ->setShippingMethod($shippingMethod); //shipping method

                $quote->setPaymentMethod($paymentMethod);
                $quote->setInventoryProcessed(false);

                $quote->getPayment()->importData(['method' => $paymentMethod]);

                $quote->collectTotals()->save();

                $this->quoteManagement->submit($quote);
            }
        }
    }

    /**
     * @return CustomerInterface[]
     */
    private function getCustomers(): array
    {
        $criteria = $this->searchCriteriaBuilder->addFilter('website_id', $this->getStoreConfig('faker/global/website_ids'), 'in');

        return $this->customerRepository->getList($criteria->create())->getItems();
    }

    /**
     * @return array
     */
    private function getProductIds(): array
    {
        return $this->productCollectionFactory->create()->addFieldToFilter('status', ['eq' => true])->getAllIds();
    }
}
