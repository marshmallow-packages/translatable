<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('/storage')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'full_opening_tag' => false,
    ])
    ->setFinder($finder)
;
