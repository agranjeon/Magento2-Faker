<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Faker\Generator;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Category extends AbstractFaker implements FakerInterface
{

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;
    /**
     * @var Generator
     */
    private $faker;

    /**
     * Category constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(ScopeConfigInterface $scopeConfig, StoreCollectionFactory $storeCollectionFactory, CategoryFactory $categoryFactory, CategoryRepositoryInterface $categoryRepository)
    {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @return void
     */
    public function generateFakeData(): void
    {
        $this->faker = $this->getFaker(0);
        $baseCategoryId = (int)$this->getStoreConfig('faker/category/parent');
        $depth = (int)$this->getStoreConfig('faker/category/max_depth');
        for ($i = 0; $i < $this->getStoreConfig('faker/category/max_number'); $i++) {
            $this->createCategory($baseCategoryId, $depth);
        }
    }

    /**
     * @param int $parentId
     * @param int $depth
     *
     * @return void
     */
    private function createCategory(int $parentId, int $depth): void
    {
        if ($depth <= 0) {
            return;
        }

        $category = $this->categoryFactory->create();
        $category->setName($this->faker->words(2, true));
        $category->setParentId($parentId);
        $category->setIsActive($this->faker->boolean(85));
        $category->setCustomAttributes([
            'description' => $this->faker->sentence(15),
            'meta_tile' => $this->faker->word,
            'meta_keywords' => $this->faker->sentence(10),
            'meta_description' => $this->faker->sentence(15),
        ]);

        $categoryId = $this->categoryRepository->save($category)->getId();
        $depth--;

        if ($depth == 0) {
            return;
        }

        $subCategoriesNumber = $this->faker->numberBetween($this->getStoreConfig('faker/category/min_number'), $this->getStoreConfig('faker/category/max_number'));
        for ($i = 0; $i < $subCategoriesNumber; $i++) {
            $this->createCategory($categoryId, $depth);
        }
    }
}
