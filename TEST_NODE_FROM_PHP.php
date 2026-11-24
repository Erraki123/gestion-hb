<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Process\Process;

$node = trim((string) shell_exec('where node'));
$node = explode("\n", $node)[0];
$node = trim($node);

echo "NODE: " . $node . PHP_EOL;

$root = getenv('SystemRoot') ?: 'C:\\Windows';

$p = new Process(
    [$node, '-e', 'console.log("NODE_FROM_PHP_OK")'],
    __DIR__,
    [
        'SystemRoot' => $root,
        'WINDIR' => $root,
    ]
);

$p->setTimeout(20);
$p->run();

echo "EXIT: " . $p->getExitCode() . PHP_EOL;
echo "OUTPUT: " . $p->getOutput() . PHP_EOL;
echo "ERROR: " . $p->getErrorOutput() . PHP_EOL;
