<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/migrations')
    ->in(__DIR__ . '/public')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->append([__DIR__ . '/yii-db-migration.php']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
