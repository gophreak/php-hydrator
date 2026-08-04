<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return new Config()
    ->setRiskyAllowed(false)
    ->setRules([
        '@auto' => true,
        '@PhpCsFixer' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant',
                'property_static',
                'property',

                // Methods: Static methods are declared first globally
                'method_static',

                // Regular methods come next.
                // Without the explicit 'construct' rule, __construct is treated as a regular public method.
                'method',
            ],
            'sort_algorithm' => 'none',
        ],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        new Finder()
            // 💡 root folder to check
            ->in(__DIR__)
    )
;
