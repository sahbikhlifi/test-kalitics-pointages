<?php

declare(strict_types=1);

// Style PSR-12 + quelques règles de propreté sans risque. Usage : make cs (corrige) / make cs-check (vérifie).
$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/migrations'])
    ->append([__DIR__ . '/.php-cs-fixer.dist.php']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        // `class Foo extends Bar` sur une seule ligne.
        'class_definition' => ['single_line' => true],
        // `if (`, `foreach (`, `fn (` : un espace après les mots-clés.
        'single_space_around_construct' => true,
        // `function foo(): void` : pas d'espace avant les deux-points.
        'return_type_declaration' => ['space_before' => 'none'],
        // Pas d'alignement en colonnes des propriétés ni des `=>` : un seul espace.
        'binary_operator_spaces' => ['default' => 'single_space'],
        'type_declaration_spaces' => ['elements' => ['function', 'property']],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'no_extra_blank_lines' => true,
        'no_blank_lines_after_class_opening' => true,
        'phpdoc_trim' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache');
