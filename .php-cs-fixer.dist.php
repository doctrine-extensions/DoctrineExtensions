<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/example',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude([
        __DIR__ . '/tests/data',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'error_suppression' => true,
        'is_null' => false,
        'list_syntax' => ['syntax' => 'short'],
        'modernize_types_casting' => true,
        'no_homoglyph_names' => true,
        'no_unset_on_property' => true,
        'no_useless_else' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'php_unit_construct' => true,
        'php_unit_method_casing' => false,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_strict' => true,
        'php_unit_test_annotation' => false,
        'php_unit_test_case_static_method_calls' => true,
        'random_api_migration' => true,
        'self_accessor' => true,
        'static_lambda' => true,
        'ternary_to_null_coalescing' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
