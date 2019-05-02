<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Faker\Generator;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Category extends AbstractFaker implements FakerInterface
{
    /**
     * @var CategoryFactory $categoryFactory
     */
    protected $categoryFactory;
    /**
     * @var CategoryRepositoryInterface $categoryRepository
     */
    protected $categoryRepository;
    /**
     * @var Generator $faker
     */
    protected $faker;

    /**
     * Category constructor.
     *
     * @param ScopeConfigInterface        $scopeConfig
     * @param StoreCollectionFactory      $storeCollectionFactory
     * @param CategoryFactory             $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreCollectionFactory $storeCollectionFactory,
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->categoryFactory    = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function generateFakeData(OutputInterface $output): void
    {
        $this->faker    = $this->getFaker(0);
        $baseCategoryId = (int)$this->getStoreConfig('faker/category/parent');
        $depth          = (int)$this->getStoreConfig('faker/category/max_depth');

        $progressBar = new ProgressBar($output->section(), (int)$this->getStoreConfig('faker/category/max_number'));
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );
        $progressBar->start();
        $progressBar->setMessage('Categories ...');
        $progressBar->display();

        for ($i = 0; $i < $this->getStoreConfig('faker/category/max_number'); $i++) {
            $this->createCategory($baseCategoryId, $depth);
            $progressBar->advance();
            $progressBar->display();
        }

        $progressBar->finish();
    }

    /**
     * @param int $parentId
     * @param int $depth
     *
     * @return void
     */
    protected function createCategory(int $parentId, int $depth): void
    {
        if ($depth <= 0) {
            return;
        }

        $category = $this->categoryFactory->create();
        $name = '';
        while (strlen($name) < 1) {
            $name = substr($this->faker->realText(20, 3), 0, -1);
        }
        $category->setName($name);
        $category->setUrlKey($this->faker->uuid);
        $category->setParentId($parentId);
        $category->setIsActive($this->faker->boolean(85));
        $category->setCustomAttributes(
            [
                'description'      => $this->faker->realText(100, 5),
                'meta_title'        => $this->faker->realText(20, 3),
                'meta_keywords'    => $this->faker->realText(50, 5),
                'meta_description' => $this->faker->realText(100, 5),
            ]
        );

        $categoryId = $this->categoryRepository->save($category)->getId();
        $depth--;

        if ($depth == 0) {
            return;
        }

        $subCategoriesNumber = $this->faker->numberBetween(
            $this->getStoreConfig('faker/category/min_number'),
            $this->getStoreConfig('faker/category/max_number')
        );
        for ($i = 0; $i < $subCategoriesNumber; $i++) {
            $this->createCategory((int)$categoryId, $depth);
        }
    }
}
