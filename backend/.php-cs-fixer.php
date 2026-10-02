<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src/Auth',
        __DIR__ . '/src/Users',
        __DIR__ . '/src/Cycles',
        __DIR__ . '/src/Subjects',
        __DIR__ . '/src/Groups',
        __DIR__ . '/src/Enrollments',
        __DIR__ . '/src/Tasks',
        __DIR__ . '/src/Calendar',
        __DIR__ . '/src/Announcements',
        __DIR__ . '/src/TaskComments',
        __DIR__ . '/src/Shared',
        __DIR__ . '/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
