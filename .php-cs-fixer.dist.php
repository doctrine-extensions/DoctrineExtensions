<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/example',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude([
        __DIR__ . '/tests/temp',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'is_null' => false,
        'list_syntax' => ['syntax' => 'short'],
        'modernize_types_casting' => true,
        'no_useless_else' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'php_unit_method_casing' => false,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_test_annotation' => false,
        'php_unit_test_case_static_method_calls' => true,
        'ternary_to_null_coalescing' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);