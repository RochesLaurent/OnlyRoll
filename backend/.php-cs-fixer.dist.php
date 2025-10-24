<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('public/bundles')
    ->notPath('src/Kernel.php')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        
        'concat_space' => ['spacing' => 'one'],
        'blank_line_before_statement' => ['statements' => ['return', 'throw', 'try']],
        'control_structure_continuation_position' => ['position' => 'next_line'],
        
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_separation' => true,
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => false],
        
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'global_namespace_import' => ['import_classes' => true],
        
        'no_trailing_whitespace' => true,
        'no_extra_blank_lines' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
        'line_ending' => true,
        
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
        'visibility_required' => ['elements' => ['property', 'method', 'const']],
        
        'modernize_types_casting' => true,
        'ternary_to_elvis_operator' => true,
        'ternary_to_null_coalescing' => true,
        
        'declare_strict_types' => false,
        'strict_comparison' => false,
        'final_class' => false,
        'yoda_style' => false,
        'single_line_throw' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/cache/.php-cs-fixer.cache');