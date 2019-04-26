<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Api;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
interface FakerInterface
{
    /**
     * @return void
     */
    public function generateFakeData(): void;
}
