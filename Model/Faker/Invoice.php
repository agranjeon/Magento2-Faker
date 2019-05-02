<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Invoice extends AbstractFaker implements FakerInterface
{
    /**
     * Description $orderCollectionFactory field
     *
     * @var OrderCollectionFactory $orderCollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * Description $transactionFactory field
     *
     * @var TransactionFactory $transactionFactory
     */
    protected $transactionFactory;

    /**
     * Invoice constructor
     *
     * @param ScopeConfigInterface   $scopeConfig
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param TransactionFactory     $transactionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreCollectionFactory $storeCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        TransactionFactory $transactionFactory
    ) {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transactionFactory     = $transactionFactory;
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function generateFakeData(OutputInterface $output): void
    {
        $orders      = $this->getOrders();
        $progressBar = new ProgressBar(
            $output->section(), $orders->getSize()
        );
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );
        $progressBar->start();
        $progressBar->setMessage('Invoice orders ...');
        $progressBar->display();
        $ratio = $this->getStoreConfig('faker/order/invoice_ratio');

        $faker = $this->getFaker(0);

        /** @var Order $order */
        foreach ($orders as $order) {
            if ($faker->boolean($ratio) && $order->canInvoice()) {
                $invoice = $order->prepareInvoice()->register();
                $order->setIsInProcess(true);
                $transaction = $this->transactionFactory->create()
                    ->addObject($invoice)
                    ->addObject($order);

                $transaction->save();
            }
            $progressBar->advance();
        }
        $progressBar->finish();
    }

    /**
     * Description getOrders function
     *
     * @return Collection
     */
    protected function getOrders(): Collection
    {
        return $this->orderCollectionFactory->create()->addFieldToFilter('status', ['eq' => 'pending']);
    }
}
