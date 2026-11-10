<?php
declare(strict_types=1);

use Robo\Core\Config;
use Robo\Core\Database;

/**
 * Minimal dependency bootstrap shared by the installer, HTTP entry points and CLI tests.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Robo\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

function robo_config(string $projectRoot): Config
{
    return Config::fromFile($projectRoot . '/config/local.php');
}

function robo_database(Config $config): Database
{
    return new Database($config);
}
