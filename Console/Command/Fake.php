<?php

declare(strict_types=1);

namespace Dnd\Faker\Console\Command;

use Dnd\Sales\Model\ResourceModel\StoreOrder\Item;
use Dnd\Sales\Model\StoreOrder;
use Faker\Factory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Fake
 *
 * @category  Class
 * @package   Dnd\Faker\Console\Command
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Fake extends Command
{
    protected $objectManager;
    protected $store;
    protected $customer;
    protected $quoteManagement;
    const PRODUCTS = [
        ['product_id' => 1, 'qty' => 1],
        ['product_id' => 2, 'qty' => 1],
        ['product_id' => 3, 'qty' => 1],
        ['product_id' => 4, 'qty' => 1],
        ['product_id' => 5, 'qty' => 1],
        ['product_id' => 6, 'qty' => 1],
        ['product_id' => 7, 'qty' => 1],
        ['product_id' => 8, 'qty' => 1],
        ['product_id' => 11, 'qty' => 1],
        ['product_id' => 12, 'qty' => 1],
        ['product_id' => 13, 'qty' => 1],
        ['product_id' => 14, 'qty' => 1],
        ['product_id' => 15, 'qty' => 1],
        ['product_id' => 16, 'qty' => 1],
        ['product_id' => 17, 'qty' => 1],
        ['product_id' => 18, 'qty' => 1],
        ['product_id' => 19, 'qty' => 1],
        ['product_id' => 20, 'qty' => 1],
        ['product_id' => 21, 'qty' => 1],
        ['product_id' => 22, 'qty' => 1],
        ['product_id' => 23, 'qty' => 1],
        ['product_id' => 24, 'qty' => 1],
        ['product_id' => 25, 'qty' => 1],
        ['product_id' => 26, 'qty' => 1],
        ['product_id' => 27, 'qty' => 1],
        ['product_id' => 28, 'qty' => 1],
        ['product_id' => 29, 'qty' => 1],
        ['product_id' => 30, 'qty' => 1],
        ['product_id' => 31, 'qty' => 1],
        ['product_id' => 32, 'qty' => 1],
    ];
    protected $product;
    /** @var CustomerRepository */
    protected $customerRepository;

    /**
     *
     */
    protected function configure()
    {
        $this->setName('dnd:fake:data')->setDescription('Create lot of fake customer and order');
    }

    /**
     * Description execute function
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var State $state */
        $state = $objectManager->get(State::class);
        try {
            $state->setAreaCode('frontend');
        } catch (\Exception $exception) {
        }
        $this->store              = $objectManager->get(StoreManager::class)->getStore();
        $this->quoteManagement    = $objectManager->get(QuoteManagement::class);
        $this->objectManager      = $objectManager;
        $this->customerRepository = $objectManager->get(CustomerRepository::class);

        $faker = Factory::create('ja_JP');

        for ($i = 6; $i < 1343; $i++) {
            /** @var Customer $customer */
            $this->customer = $objectManager->create(Customer::class);
            $this->customer->setData(
                [
                    'website_id'   => 1,
                    'firstname'    => $faker->firstName,
                    'lastname'     => $faker->lastName,
                    'dob'          => $faker->date(),
                    'email'        => uniqid() . $faker->email,
                    'password'     => $faker->password,
                    'phone_number' => $faker->phoneNumber,
                ]
            );
            $this->customer->save();

            $numberOfOrder = rand(0, 4);
            for ($e = 0; $e < $numberOfOrder; $e++) {
                $orderItems   = [];
                $numberOfItem = 1 + rand(0, 2);
                for ($eeh = 0; $eeh < $numberOfItem; $eeh++) {
                    $orderItems[] = self::PRODUCTS[mt_rand(0, count(self::PRODUCTS) - 1)];
                }

                $order = [
                    'currency_id'      => 'EUR',
                    'email'            => $this->customer->getEmail(), //buyer email id
                    'shipping_address' => [
                        'firstname'            => $this->customer->getFirstname(), //address Details
                        'lastname'             => $this->customer->getLastname(),
                        'street'               => $faker->streetAddress,
                        'city'                 => $faker->city,
                        'country_id'           => 'FR',
                        'postcode'             => $faker->postcode,
                        'telephone'            => $this->customer->getPhoneNumber(),
                        'save_in_address_book' => 1,
                    ],
                    'items'            => $orderItems,
                ];

                $this->createOrder($order);

                $storeOrderItems         = [];
                $numberOfStoreOrderItems = 1 + rand(0, 2);
                for ($gneh = 0; $gneh < $numberOfStoreOrderItems; $gneh++) {
                    $sku               = rand(0, 2) > 1 ? 'RAYB123' : uniqid();
                    $attributeSetId    = rand(10, 12);
                    $storeOrderItems[] = [
                        'sku' => $sku,
                        'name' => $faker->realText(10),
                        'attribute_set' => $attributeSetId,
                    ];
                }

                $storeOrder = [
                    'generix_id'  => $faker->uuid,
                    'customer_id' => $this->customer->getId(),
                    'date'        => $faker->dateTimeBetween('-2days'),
                    'total'       => $faker->numberBetween(5, 200),
                    'items'       => $storeOrderItems,
                ];

                $this->createStoreOrder($storeOrder);
            }
        }
    }

    public function createOrder($orderData)
    {
        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->setStore($this->store);
        $quote->setCurrency();
        $customer = $this->customerRepository->get($this->customer->getEmail());
        $quote->assignCustomer($customer); //Assign quote to customer

        //add items in quote
        foreach ($orderData['items'] as $item) {
            $product = $this->objectManager->create(Product::class)->load($item['product_id']);
            $quote->addProduct(
                $product,
                intval($item['qty'])
            );
        }

        //Set Address to quote
        $quote->getBillingAddress()->addData($orderData['shipping_address']);
        $quote->getShippingAddress()->addData($orderData['shipping_address']);

        // Collect Rates and Set Shipping & Payment Method

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(1)->collectShippingRates()->setShippingMethod(
            'flatrate_flatrate'
        ); //shipping method
        $quote->setPaymentMethod('checkmo'); //payment method
        $quote->setInventoryProcessed(false); //not effetc inventory
        $quote->save(); //Now Save quote and your quote is ready

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'checkmo']);

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // Create Order From Quote
        $order = $this->quoteManagement->submit($quote);
    }

    public function createStoreOrder($storeOrderData)
    {
        $storeOrder = $this->objectManager->create(StoreOrder::class);
        $storeOrder->setData($storeOrderData);
        // Save the store order
        $this->objectManager->get(\Dnd\Sales\Model\ResourceModel\StoreOrder::class)->save($storeOrder);

        /** @var int $orderId */
        $orderId    = (int)$storeOrder->getId();
        $orderItems = $storeOrderData['items'];

        /** @var array $orderItemData */
        foreach ($orderItems as $orderItemData) {
            $orderItem = $this->objectManager->create(StoreOrder\Item::class);
            $orderItem->setData($orderItemData);
            $orderItem->setOrderId($orderId);

            // Save the store order items
            $this->objectManager->get(Item::class)->save($orderItem);
        }
    }
}
