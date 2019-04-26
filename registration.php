<?php

declare(strict_types=1);

/**
 * @category  Registrar
 * @package   Dnd\Faker
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Dnd_Faker',
    __DIR__
);
