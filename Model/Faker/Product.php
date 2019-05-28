<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Product extends AbstractFaker implements FakerInterface
{
    /**
     * @var ProductFactory $productFactory
     */
    protected $productFactory;
    /**
     * @var ProductRepositoryInterface $productRepository
     */
    protected $productRepository;
    /**
     * @var CategoryCollectionFactory $categoryCollectionFactory
     */
    protected $categoryCollectionFactory;
    /**
     * @var CategoryLinkManagementInterface $categoryLinkManagement
     */
    protected $categoryLinkManagement;

    /**
     * Product constructor.
     *
     * @param ScopeConfigInterface            $scopeConfig
     * @param StoreCollectionFactory          $storeCollectionFactory
     * @param ProductFactory                  $productFactory
     * @param ProductRepositoryInterface      $productRepository
     * @param CategoryCollectionFactory       $categoryCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreCollectionFactory $storeCollectionFactory,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement
    ) {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->productFactory            = $productFactory;
        $this->productRepository         = $productRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryLinkManagement    = $categoryLinkManagement;
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function generateFakeData(OutputInterface $output): void
    {
        $numberOfProduct = (int)$this->getStoreConfig('faker/product/number');
        $websiteIds      = explode(',', $this->getStoreConfig('faker/global/website_ids'));
        $faker           = $this->getFaker(0);
        $categoryIds     = $this->categoryCollectionFactory->create()->getAllIds();
        $progressBar     = new ProgressBar($output->section(), $numberOfProduct);
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );
        $progressBar->start();
        $progressBar->setMessage('Products ...');
        $progressBar->display();

        //todo: optimize this (how?). very slow
        for ($i = 0; $i < $numberOfProduct; $i++) {
            $product = $this->productFactory->create();
            $product->setSku(uniqid());
            $product->setStatus($faker->boolean(90));
            $name = '';
            while (strlen($name) < 1) {
                $name = substr($faker->realText($faker->numberBetween(15, 25), 3), 0, -1);
            }
            $product->setName($name);
            $product->setUrlKey($faker->uuid);
            $product->setWebsiteIds($websiteIds);
            $product->setTypeId('simple');
            // Todo: retrieve available attribute set for product type and use a random one. Fill attribute set's attributes values
            // Todo: randomly generate configurable products

            $product->setAttributeSetId(4);
            $product->setVisibility(4);
            $product->setPrice($faker->numberBetween(5, 100));
            $product->setStockData(
                [
                    'is_in_stock' => $faker->boolean(90),
                    'qty'         => $faker->numberBetween(0, 100),
                ]
            );

            $this->productRepository->save($product);

            $productCategoryIds = array_rand(
                $categoryIds,
                $faker->numberBetween(
                    $this->getStoreConfig('faker/product/min_category_number'),
                    $this->getStoreConfig('faker/product/max_category_number')
                )
            );

            if (!is_array($productCategoryIds)) {
                $productCategoryIds = [$productCategoryIds];
            }

            $productCategories = [];
            foreach ($productCategoryIds as $categoryId) {
                $productCategories[] = $categoryIds[$categoryId];
            }

            if (!empty($productCategories)) {
                $this->categoryLinkManagement->assignProductToCategories(
                    $product->getSku(),
                    $productCategories
                );
            }

            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
