<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model;

use Agranjeon\Faker\Api\FakerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class FakerProvider
{
    /**
     * @var FakerInterface[] $fakerList
     */
    private $fakerList;

    /**
     * FakerProvider constructor
     *
     * @param array $fakerList
     */
    public function __construct(
        array $fakerList = []
    ) {
        $this->fakerList = $fakerList;
    }

    /**
     * Retrieve a Faker object by its code
     *
     * @param string $code
     *
     * @return FakerInterface
     * @throws LocalizedException
     */
    public function getFaker(string $code): FakerInterface
    {
        if (!array_key_exists($code, $this->fakerList)) {
            throw new LocalizedException(__('Faker %s does not exist', $code));
        }

        return $this->fakerList[$code];
    }

    /**
     * Retrieve all Faker objects
     *
     * @return FakerInterface[]
     */
    public function getFakers(): array
    {
        return $this->fakerList;
    }
}
