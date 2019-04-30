<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Api;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
interface FakerInterface
{
    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function generateFakeData(OutputInterface $output): void;
}
