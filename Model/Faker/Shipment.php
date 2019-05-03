<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Sales\Model\Convert\Order as OrderConverter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Shipment extends AbstractFaker implements FakerInterface
{
    /**
     * @var OrderCollectionFactory $orderCollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var TransactionFactory $transactionFactory
     */
    protected $transactionFactory;
    /**
     * @var OrderConverter $orderConverter
     */
    protected $orderConverter;

    /**
     * Invoice constructor
     *
     * @param ScopeConfigInterface   $scopeConfig
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderConverter         $orderConverter
     * @param TransactionFactory     $transactionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreCollectionFactory $storeCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        OrderConverter $orderConverter,
        TransactionFactory $transactionFactory
    ) {
        parent::__construct($scopeConfig, $storeCollectionFactory);

        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transactionFactory     = $transactionFactory;
        $this->orderConverter         = $orderConverter;
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
        $progressBar->setMessage('Ship orders ...');
        $progressBar->display();
        $ratio = $this->getStoreConfig('faker/order/shipment_ratio');

        $faker = $this->getFaker(0);

        /** @var Order $order */
        foreach ($orders as $order) {
            if ($faker->boolean($ratio) && $order->canShip()) {
                try {
                    $shipment = $this->orderConverter->toShipment($order);
                    foreach ($order->getAllVisibleItems() as $item) {
                        if ($item->getIsVirtual() || !$item->getQtyToShip()) {
                            continue;
                        }

                        $qty = $item->getQtyToShip();
                        $shipmentItem = $this->orderConverter->itemToShipmentItem($item)->setQty($qty);
                        $shipment->addItem($shipmentItem);
                    }
                    $shipment->register();
                    $order->setIsInProcess(true);
                    $transaction = $this->transactionFactory->create()
                        ->addObject($shipment)
                        ->addObject($order);

                    $transaction->save();
                } catch(\Exception $exception) {
                }

            }
            $progressBar->advance();
        }
        $progressBar->finish();
    }

    /**
     * @return Collection
     */
    protected function getOrders(): Collection
    {
        return $this->orderCollectionFactory->create()->addFieldToFilter('status', ['eq' => 'processing']);
    }
}
