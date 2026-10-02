<?php

$roots = [
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
];
$testModules = ['Auth', 'Users', 'Cycles', 'Subjects', 'Groups', 'Enrollments', 'Tasks', 'Calendar', 'Announcements', 'TaskComments'];
$failed = false;

foreach ($roots as $root) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($files as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        if ($root === __DIR__ . '/tests') {
            $relativePath = substr($file->getPathname(), strlen($root) + 1);
            $module = explode(DIRECTORY_SEPARATOR, $relativePath)[0];
            if ($module !== '' && !in_array($module, $testModules, true)) {
                continue;
            }
        }
        foreach (file($file->getPathname()) as $index => $line) {
            $length = strlen(rtrim($line, "\r\n"));
            if ($length > 120) {
                fwrite(STDERR, sprintf("%s:%d: %d columns\n", $file->getPathname(), $index + 1, $length));
                $failed = true;
            }
        }
    }
}

exit($failed ? 1 : 0);
