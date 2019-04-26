<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Locale implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'fr_FR', 'label' => 'Français']
        ];
    }
}
