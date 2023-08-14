<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src/');

$fixer = new PhpCsFixer\Config();
return $fixer->setRules([
        '@PSR2' => true
    ])
    ->setFinder($finder);

