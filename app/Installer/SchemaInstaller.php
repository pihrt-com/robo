<?php
declare(strict_types=1);

namespace Robo\Installer;

use PDO;
use RuntimeException;

final class SchemaInstaller
{
    public function __construct(private readonly PDO $pdo, private readonly string $migrationDirectory)
    {
    }

    public function install(): void
    {
        foreach (glob($this->migrationDirectory . '/*.sql') ?: [] as $file) {
            $sql = trim((string) file_get_contents($file));
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
                if (trim($statement) === '') {
                    continue;
                }
                try {
                    $this->pdo->exec($statement);
                } catch (\Throwable $exception) {
                    throw new RuntimeException('Databázovou strukturu se nepodařilo vytvořit.', 0, $exception);
                }
            }
        }
    }
}
