<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/BackendContractsTest.php';

use Robo\Tests\BackendContractsTest;

try {
    BackendContractsTest::run();
    fwrite(STDOUT, "Backend contract tests passed.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Backend contract tests failed: {$exception->getMessage()}\n");
    exit(1);
}
