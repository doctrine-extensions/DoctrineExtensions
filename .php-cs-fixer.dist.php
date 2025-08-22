<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$header = <<<'HEADER'
    This file is part of the Doctrine Behavioral Extensions package.
    (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    HEADER;

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/example',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->append([__FILE__, __DIR__.'/rector.php'])
    ->exclude([
        __DIR__.'/tests/data',
    ]);

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PHP74Migration' => true,
        '@PHP74Migration:risky' => true,
        '@PHPUnit91Migration:risky' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        // @todo: Change the following rule to `true` in the next major release.
        'declare_strict_types' => false,
        'error_suppression' => true,
        'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'header_comment' => ['header' => $header],
        'is_null' => true,
        'list_syntax' => ['syntax' => 'short'],
        'modernize_types_casting' => true,
        'no_homoglyph_names' => true,
        'no_null_property_initialization' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_unset_on_property' => true,
        'no_useless_else' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_order' => ['order' => ['param', 'throws', 'return']],
        'phpdoc_separation' => ['groups' => [
            ['Gedmo\\*'],
            ['ODM\\*'],
            ['ORM\\*'],
        ]],
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => true,
        'php_unit_dedicate_assert_internal_type' => true,
        'php_unit_mock' => true,
        'php_unit_namespaced' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_strict' => true,
        'php_unit_test_annotation' => ['style' => 'prefix'],
        'php_unit_test_case_static_method_calls' => true,
        'psr_autoloading' => true,
        'random_api_migration' => true,
        'return_assignment' => true,
        'self_accessor' => true,
        'static_lambda' => true,
        'strict_param' => true,
        'ternary_to_null_coalescing' => true,
        'trailing_comma_in_multiline' => [
            'elements' => [
                'arrays',
            ],
        ],
        // @todo: Change the following rule to `true` in the next major release.
        'void_return' => false,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
