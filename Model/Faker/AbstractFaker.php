<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
abstract class AbstractFaker
{
    /**
     * Description $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * AbstractFaker constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string                         $path
     * @param ScopeInterface|int|string|null $store
     *
     * @return mixed
     */
    protected function getStoreConfig(string $path, $store = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }
}
