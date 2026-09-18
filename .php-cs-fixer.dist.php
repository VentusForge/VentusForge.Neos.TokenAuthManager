<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php');

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@DoctrineAnnotation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'mb_str_functions' => true,
        'set_type_to_cast' => true,
        'attribute_empty_parentheses' => true,
        'numeric_literal_separator' => ['override_existing' => true, 'strategy' => 'use_separator'],
        'ordered_attributes' => true,
        'ordered_interfaces' => true,
        '@PHP7x0Migration' => true,
        '@PHP7x1Migration' => true,
        '@PHP7x3Migration' => true,
        '@PHP7x4Migration' => true,
        '@PHP8x0Migration' => true,
        '@PHP8x1Migration' => true,
        '@PHP8x2Migration' => true,
        '@PHP8x3Migration' => true,
        'modernize_strpos' => true,
        'stringable_for_to_string' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'method_chaining_indentation' => true,
        'is_null' => true,
        'no_empty_statement' => true,
        'phpdoc_param_order' => true,
        'no_useless_return' => true,
        'no_useless_else' => true,
        'simplified_if_return' => true,
        'modernize_types_casting' => true,
        'fully_qualified_strict_types' => true,
        'no_unused_imports' => true,
        'combine_nested_dirname' => true,
        'fopen_flag_order' => true,
        'fopen_flags' => true,
        'implode_call' => true,
        'regular_callable_call' => true,
        'no_superfluous_elseif' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'return_to_yield_from' => true,
        'heredoc_indentation' => ['indentation' => 'same_as_start'],
        'header_comment' => 'This file is part of the VentusForge.Toolbox package.',
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
