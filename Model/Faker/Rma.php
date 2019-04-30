<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Faker;

use Agranjeon\Faker\Api\FakerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Rma extends AbstractFaker implements FakerInterface
{
    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function generateFakeData(OutputInterface $output): void
    {
        // @TODO Implement generateFakeData() method.
    }
}
