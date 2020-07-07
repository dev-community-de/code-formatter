<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setFinder($finder)
    ->setIndent('    ')
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'no_superfluous_phpdoc_tags' => false,
        'blank_line_before_statement' => [
            'statements' => [],
        ],
        'single_blank_line_before_namespace' => false,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_typehint' => true,
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'fully_qualified_strict_types' => true,
        'method_chaining_indentation' => true,
        'ordered_class_elements' => false,
        'ordered_imports' => true,
        'concat_space' => ['spacing' => 'one'],
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
            'scope' => 'namespaced',
        ],
        'single_line_throw' => false,
    ]);
