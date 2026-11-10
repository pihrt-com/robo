<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/BackendContractsTest.php';
require __DIR__ . '/TeamImportContractTest.php';
require __DIR__ . '/AdminValidationTest.php';
require __DIR__ . '/UpdateContractsTest.php';

use Robo\Tests\BackendContractsTest;
use Robo\Tests\TeamImportContractTest;
use Robo\Tests\AdminValidationTest;
use Robo\Tests\UpdateContractsTest;

try {
    BackendContractsTest::run();
    TeamImportContractTest::run();
    AdminValidationTest::run();
    UpdateContractsTest::run();
    fwrite(STDOUT, "Backend contract tests passed.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Backend contract tests failed: {$exception->getMessage()}\n");
    exit(1);
}
