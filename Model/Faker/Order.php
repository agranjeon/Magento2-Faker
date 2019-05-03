<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Order extends AbstractFaker implements FakerInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var ProductFactory
     */
    protected $productFactory;
    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var Stock $stockFilter
     */
    protected $stockFilter;

    /**
     * Order constructor.
     *
     * @param ScopeConfigInterface        $scopeConfig
     * @param StoreCollectionFactory      $storeCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface       $storeManager
     * @param QuoteFactory                $quoteFactory
     * @param ProductFactory              $productFactory
     * @param CartManagementInterface     $quoteManagement
     * @param SearchCriteriaBuilder       $searchCriteriaBuilder
     * @param CollectionFactory           $productCollectionFactory
     * @param Stock                       $stockFilter
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreCollectionFactory $storeCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        QuoteFactory $quoteFactory,
        ProductFactory $productFactory,
        CartManagementInterface $quoteManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $productCollectionFactory,
        Stock $stockFilter
    ) {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->customerRepository       = $customerRepository;
        $this->storeManager             = $storeManager;
        $this->quoteFactory             = $quoteFactory;
        $this->productFactory           = $productFactory;
        $this->quoteManagement          = $quoteManagement;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockFilter              = $stockFilter;
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function generateFakeData(OutputInterface $output): void
    {
        $productIds  = $this->getProductIds();
        $customers   = $this->getCustomers();
        $progressBar = new ProgressBar(
            $output->section(), count($customers)
        );
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );
        $progressBar->start();
        $progressBar->setMessage('Orders ...');
        $progressBar->display();

        $e = 0;
        foreach ($customers as $customer) {
            $e++;
            $store = $this->storeManager->getStore($customer->getStoreId());
            if (!$store->getIsActive()) {
                continue;
            }
            $availableShippingMethods = explode(',', $this->getStoreConfig('faker/order/shipping_method', $store));
            $availablePaymentMethods  = explode(',', $this->getStoreConfig('faker/order/payment_method', $store));

            $numberOfOrders = $this->getStoreConfig('faker/order/number', $store);
            for ($i = 0; $i < $numberOfOrders; $i++) {
                $shippingMethod = $availableShippingMethods[array_rand($availableShippingMethods)];
                $paymentMethod  = $availablePaymentMethods[array_rand($availablePaymentMethods)];

                $quote = $this->quoteFactory->create();
                $quote->setStore($store);
                $quote->setCurrency();
                $quote->assignCustomer($customer);

                $shippingAddress = $customer->getAddresses();
                $numberOfItems   = rand(
                    (int)$this->getStoreConfig('faker/order/min_items_number', $store),
                    (int)$this->getStoreConfig('faker/order/max_items_number', $store)
                );

                for ($i = 0; $i < $numberOfItems; $i++) {
                    try {
                        $product = $this->productFactory->create()->load($productIds[array_rand($productIds)]);
                        $quote->addProduct(
                            $product,
                            rand(1, 3) // qty
                        );
                    } catch(\Exception $exception) {
                    }
                }
                if (count($quote->getItemsCollection()->getItems()) == 0) {
                    continue;
                }

                $quote->getBillingAddress()->importCustomerAddressData(reset($shippingAddress));
                $quote->getShippingAddress()->importCustomerAddressData(reset($shippingAddress));

                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setCollectShippingRates(1)->collectShippingRates()->setShippingMethod(
                    $shippingMethod
                );

                $quote->setPaymentMethod($paymentMethod);
                $quote->setInventoryProcessed(false);
                try {
                    $quote->save();
                    $quote->getPayment()->importData(['method' => $paymentMethod]);
                    $quote->collectTotals()->save();
                    $this->quoteManagement->submit($quote);
                } catch(\Exception $exception) {
                }
            }
            $progressBar->advance();
        }
        $progressBar->finish();
    }

    /**
     * @return CustomerInterface[]
     */
    protected function getCustomers(): array
    {
        $criteria = $this->searchCriteriaBuilder->addFilter(
            'website_id',
            $this->getStoreConfig('faker/global/website_ids'),
            'in'
        );

        return $this->customerRepository->getList($criteria->create())->getItems();
    }

    /**
     * @return int[]
     */
    protected function getProductIds(): array
    {
        $collection = $this->productCollectionFactory->create()->addFieldToFilter('status', ['eq' => true]);
        $this->stockFilter->addIsInStockFilterToCollection($collection);
        return $collection->getAllIds();
    }
}
