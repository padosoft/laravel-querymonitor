<?php

//DOC: https://cs.symfony.com/doc/usage.html
//CONFIGURATORE: https://mlocati.github.io/php-cs-fixer-configurator/#version:3.59

$finder = PhpCsFixer\Finder::create()
    ->exclude('boostrap')
    ->exclude('deploy')
    ->exclude('node_modules')
    ->exclude('public')
    ->exclude('storage')
    //->exclude('tests')
    ->exclude('utility')
    ->exclude('vendor')
    ->exclude('workflows')
    //->notPath('src/Symfony/Component/Translation/Tests/fixtures/resources.php')
    ->in(__DIR__)
    //->in('app/Actions')
    ->name('*.php')
    //->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
;

// Controlla se viene eseguito con Laravel Pint o con php cs fixer
$isLaravelPint = false;
if (isset($_SERVER['argv'][0])) {
    $isLaravelPint = str_contains($_SERVER['argv'][0], 'pint');
}

// Setta il preset
$preset = $isLaravelPint ? '@Laravel' : '@PSR12';

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    /*->registerCustomFixers([
        new Quality\CustomMultilineChainedCallsSemicolonOnNewLineFixer(),
    ])*/
    ->setRules([
        $preset => true,
        '@PSR12' => true,
        // Proprietà non comuni ai due preset Laravel e PSR12
        'align_multiline_comment' => true,
        'array_indentation' => true,
        'class_definition' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_type_declaration' => true,
        'declare_parentheses' => true,
        'linebreak_after_opening_tag' => true,
        'multiline_comment_opening_closing' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
        'no_null_property_initialization' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_traits' => false,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order_by_value' => true,
        'random_api_migration' => true,
        // Proprietà personalizzate surface
        'cast_spaces' => false,
        'not_operator_with_successor_space' => false,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'native_function_invocation' => false,
        'blank_line_before_statement' => [
            'statements' => [
                'return',
            ],
        ],
        'method_chaining_indentation' => true,
        'trailing_comma_in_multiline' => true,
        'single_quote' => true,
        'nullable_type_declaration' => true,
        'no_unused_imports' => true,
        'lambda_not_used_import' => false,
        'space_after_semicolon' => true,
        'phpdoc_to_return_type' => false,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'class_attributes_separation' => [
            'elements' => ['const' => 'one', 'method' => 'one', 'property' => 'none', 'trait_import' => 'none', 'case' => 'none'],
        ],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_private',
                'constant_protected',
                'constant_public',
                'property_private',
                'property_protected',
                'property_public',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_private',
                'method_protected',
                'method_public',
            ],
        ],
        'phpdoc_align' => true,
        'no_superfluous_phpdoc_tags' => true,
        'braces' => [
            'position_after_anonymous_constructs' => 'same',
            'position_after_control_structures' => 'next',
            'position_after_functions_and_oop_constructs' => 'next',
            'allow_single_line_closure' => false,
            'position' => 'next',
        ],
        'single_line_empty_body' => false,
        //custom rules => l'ultima volta se abilitata lo script caricava un botto e andava in timeout
        // in ogni caso prima di abilitarla scoprire come far girare laravel pint con custom rules
        //perchè se si abilita solo qui e non su pint.json non siamo allineati sui formatter
        //'Quality/custom_multiline_chained_calls_semicolon_on_new_line_fixer' => true,
    ])
    ->setFinder($finder)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;
