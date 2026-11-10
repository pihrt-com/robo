<?php
declare(strict_types=1);

namespace Robo\Domain;

use PDO;
use RuntimeException;
use ZipArchive;

final class BackupService
{
    private const MAX_ARCHIVE_BYTES = 209715200;
    private const MAX_EXTRACTED_BYTES = 524288000;
    private const TABLES = ['app_settings', 'competitions', 'roles', 'users', 'teams', 'page_texts', 'penalties', 'technical_inspections', 'rides', 'judging_categories', 'judging_criteria', 'judging_scores', 'playoff_matches', 'login_rate_limits', 'audit_log'];
    private const ROOT_FILES = ['.gitattributes', '.gitignore', '.htaccess', 'CHANGELOG.md', 'README.md', 'VERSION', 'cache-refresh.php', 'cache-repair.php', 'index.php', 'install.php'];
    private const DIRECTORIES = ['app', 'config', 'design', 'migrations', 'public', 'tests', 'views'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{filename:string,path:string,size:int} */
    public function create(string $projectRoot, string $password): array
    {
        $this->requireZip();
        AdminValidation::password($password);
        $directory = $projectRoot . '/storage/backups';
        if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
            throw new RuntimeException('Adresář pro zálohy nelze vytvořit.');
        }
        $filename = 'robo-backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.zip';
        $path = $directory . '/' . $filename;
        $database = json_encode($this->databaseData(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $files = $this->systemFiles($projectRoot);
        $manifest = ['format' => 1, 'created_at' => date(DATE_ATOM), 'version' => trim((string) file_get_contents($projectRoot . '/VERSION')), 'database_sha256' => hash('sha256', $database), 'files' => []];
        foreach ($files as $relative => $source) {
            $manifest['files'][$relative] = hash_file('sha256', $source);
        }
        ksort($manifest['files']);
        $manifestJson = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('Soubor zálohy nelze vytvořit.');
        }
        try {
            $zip->setPassword($password);
            $zip->addFromString('manifest.json', $manifestJson);
            $zip->addFromString('database.json', $database);
            if (!$zip->setEncryptionName('database.json', ZipArchive::EM_AES_256, $password)) throw new RuntimeException('Databázovou část zálohy nelze zašifrovat.');
            $zip->addFromString('signature.txt', hash_hmac('sha256', $manifestJson, hash('sha256', $password, true)));
            if (!$zip->setEncryptionName('signature.txt', ZipArchive::EM_AES_256, $password)) throw new RuntimeException('Podpis zálohy nelze zašifrovat.');
            foreach ($files as $relative => $source) {
                if (!$zip->addFile($source, 'system/' . $relative)) {
                    throw new RuntimeException('Do zálohy nelze přidat systémový soubor.');
                }
                if (!$zip->setEncryptionName('system/' . $relative, ZipArchive::EM_AES_256, $password)) throw new RuntimeException('Systémový soubor zálohy nelze zašifrovat.');
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($path);
            throw $exception;
        } finally {
            @$zip->close();
        }
        @chmod($path, 0600);
        return ['filename' => $filename, 'path' => $path, 'size' => (int) filesize($path)];
    }

    /** @return list<array{filename:string,size:int,created_at:string}> */
    public function list(string $projectRoot): array
    {
        $directory = $projectRoot . '/storage/backups';
        $rows = [];
        foreach (is_dir($directory) ? (glob($directory . '/robo-backup-*.zip') ?: []) : [] as $path) {
            if (!is_file($path)) continue;
            $rows[] = ['filename' => basename($path), 'size' => (int) filesize($path), 'created_at' => date('d. m. Y H:i:s', (int) filemtime($path))];
        }
        usort($rows, static fn (array $a, array $b): int => strcmp($b['filename'], $a['filename']));
        return $rows;
    }

    public function path(string $projectRoot, string $filename): string
    {
        if (preg_match('/^robo-backup-[0-9]{8}-[0-9]{6}-[a-f0-9]{6}\.zip$/', $filename) !== 1) {
            throw new RuntimeException('Název zálohy není platný.');
        }
        $path = $projectRoot . '/storage/backups/' . $filename;
        if (!is_file($path)) throw new RuntimeException('Záloha nebyla nalezena.');
        return $path;
    }

    /** @return array{version:string,tables:int,files:int} */
    public function restore(string $projectRoot, string $archive, string $password): array
    {
        $this->requireZip();
        AdminValidation::password($password);
        if (!is_file($archive) || (int) filesize($archive) > self::MAX_ARCHIVE_BYTES) throw new RuntimeException('Soubor zálohy chybí nebo je příliš velký.');
        $zip = new ZipArchive();
        if ($zip->open($archive, ZipArchive::RDONLY) !== true) throw new RuntimeException('Soubor není platný ZIP archiv.');
        $work = sys_get_temp_dir() . '/robo-restore-' . bin2hex(random_bytes(8));
        $stage = $work . '/stage';
        $rollback = $work . '/rollback';
        if (!mkdir($stage, 0700, true) || !mkdir($rollback, 0700, true)) throw new RuntimeException('Nelze připravit pracovní adresář obnovy.');
        $changed = [];
        $removed = [];
        try {
            $zip->setPassword($password);
            $manifestJson = $zip->getFromName('manifest.json');
            $databaseJson = $zip->getFromName('database.json');
            $signature = $zip->getFromName('signature.txt');
            if (!is_string($manifestJson) || !is_string($databaseJson) || !is_string($signature) || !hash_equals(hash_hmac('sha256', $manifestJson, hash('sha256', $password, true)), trim($signature))) {
                throw new RuntimeException('Heslo zálohy není správné nebo je archiv poškozený.');
            }
            $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
            $database = json_decode($databaseJson, true, 512, JSON_THROW_ON_ERROR);
            if (($manifest['format'] ?? null) !== 1 || !hash_equals((string) ($manifest['database_sha256'] ?? ''), hash('sha256', $databaseJson)) || !is_array($manifest['files'] ?? null)) {
                throw new RuntimeException('Obsah zálohy je poškozený.');
            }
            $extractedBytes = strlen($databaseJson);
            foreach ($manifest['files'] as $relative => $expectedHash) {
                $relative = self::assertSafeRelativePath((string) $relative);
                $entry = 'system/' . $relative;
                $stat = $zip->statName($entry);
                $extractedBytes += is_array($stat) ? (int)($stat['size'] ?? 0) : 0;
                if ($extractedBytes > self::MAX_EXTRACTED_BYTES) throw new RuntimeException('Rozbalený obsah zálohy je příliš velký.');
                $content = $zip->getFromName($entry);
                if (!is_string($content) || !hash_equals((string) $expectedHash, hash('sha256', $content))) throw new RuntimeException('Kontrolní součet systémového souboru nesouhlasí.');
                $target = $stage . '/' . $relative;
                if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0700, true)) throw new RuntimeException('Nelze připravit systémové soubory obnovy.');
                if (file_put_contents($target, $content, LOCK_EX) === false) throw new RuntimeException('Nelze připravit systémový soubor obnovy.');
            }
            foreach ($this->systemFiles($projectRoot) as $relative => $currentPath) {
                if (array_key_exists($relative, $manifest['files'])) continue;
                $copy = $rollback . '/' . $relative;
                if (!is_dir(dirname($copy)) && !mkdir(dirname($copy), 0700, true)) throw new RuntimeException('Nelze připravit návrat nadbytečných systémových souborů.');
                if (!copy($currentPath, $copy) || !unlink($currentPath)) throw new RuntimeException('Nelze odstranit soubor, který v záloze neexistuje.');
                $removed[] = $relative;
            }
            foreach (array_keys($manifest['files']) as $relative) {
                $relative = self::assertSafeRelativePath((string) $relative);
                $target = $projectRoot . '/' . $relative;
                if (is_file($target)) {
                    $copy = $rollback . '/' . $relative;
                    if (!is_dir(dirname($copy)) && !mkdir(dirname($copy), 0700, true)) throw new RuntimeException('Nelze připravit návrat systémových souborů.');
                    if (!copy($target, $copy)) throw new RuntimeException('Nelze zálohovat současný systémový soubor.');
                }
                if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0750, true)) throw new RuntimeException('Nelze vytvořit adresář obnoveného systému.');
                $temporary = $target . '.robo-restore-' . bin2hex(random_bytes(3));
                if (!copy($stage . '/' . $relative, $temporary) || !rename($temporary, $target)) throw new RuntimeException('Nelze obnovit systémový soubor.');
                $changed[] = $relative;
            }
            $this->restoreDatabase($database);
            if (function_exists('opcache_reset')) @opcache_reset();
            return ['version' => (string) ($manifest['version'] ?? ''), 'tables' => count(self::TABLES), 'files' => count($changed)];
        } catch (\Throwable $exception) {
            foreach (array_reverse($changed) as $relative) {
                $copy = $rollback . '/' . $relative;
                if (is_file($copy)) @copy($copy, $projectRoot . '/' . $relative);
                else @unlink($projectRoot . '/' . $relative);
            }
            foreach ($removed as $relative) {
                $copy = $rollback . '/' . $relative;
                $target = $projectRoot . '/' . $relative;
                if (is_file($copy)) {
                    if (!is_dir(dirname($target))) @mkdir(dirname($target), 0750, true);
                    @copy($copy, $target);
                }
            }
            throw $exception instanceof RuntimeException ? $exception : new RuntimeException('Obnova zálohy selhala.', 0, $exception);
        } finally {
            @$zip->close();
            self::deleteTree($work);
        }
    }

    private function databaseData(): array
    {
        $data = ['tables' => []];
        foreach (self::TABLES as $table) $data['tables'][$table] = $this->pdo->query('SELECT * FROM `' . $table . '`')->fetchAll();
        return $data;
    }

    private function restoreDatabase(array $database): void
    {
        $tables = $database['tables'] ?? null;
        if (!is_array($tables) || array_keys($tables) !== self::TABLES) throw new RuntimeException('Záloha databáze nemá očekávanou strukturu.');
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach (array_reverse(self::TABLES) as $table) $this->pdo->exec('DELETE FROM `' . $table . '`');
            foreach (self::TABLES as $table) {
                if (!is_array($tables[$table])) throw new RuntimeException('Tabulka v záloze databáze není platná.');
                foreach ($tables[$table] as $row) {
                    if (!is_array($row) || $row === []) continue;
                    $columns = array_keys($row);
                    foreach ($columns as $column) if (!is_string($column) || preg_match('/^[A-Za-z0-9_]+$/', $column) !== 1) throw new RuntimeException('Záloha databáze obsahuje nepovolený název sloupce.');
                    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
                    $this->pdo->prepare($sql)->execute(array_values($row));
                }
            }
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            throw $exception;
        }
    }

    /** @return array<string,string> */
    private function systemFiles(string $projectRoot): array
    {
        $files = [];
        foreach (self::ROOT_FILES as $relative) if (is_file($projectRoot . '/' . $relative)) $files[$relative] = $projectRoot . '/' . $relative;
        foreach (self::DIRECTORIES as $directory) {
            $root = $projectRoot . '/' . $directory;
            if (!is_dir($root)) continue;
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($projectRoot) + 1));
                $files[$relative] = $file->getPathname();
            }
        }
        ksort($files);
        return $files;
    }

    private function requireZip(): void
    {
        if (!class_exists(ZipArchive::class) || !method_exists(ZipArchive::class, 'setEncryptionName') || !defined(ZipArchive::class . '::EM_AES_256')) throw new RuntimeException('Server nemá podporu ZIP AES-256 potřebnou pro zašifrované zálohy.');
    }

    public static function assertSafeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1 || str_contains($path, "\0")) throw new RuntimeException('Záloha obsahuje nepovolenou cestu.');
        foreach (explode('/', $path) as $part) if ($part === '' || $part === '.' || $part === '..') throw new RuntimeException('Záloha obsahuje nepovolenou cestu.');
        if (str_starts_with($path, '.git/')) throw new RuntimeException('Záloha obsahuje chráněný soubor.');
        $parts = explode('/', $path);
        if (!in_array($path, self::ROOT_FILES, true) && !in_array($parts[0], self::DIRECTORIES, true)) throw new RuntimeException('Záloha obsahuje soubor mimo systém ROBO.');
        return $path;
    }

    private static function deleteTree(string $directory): void
    {
        if (!is_dir($directory)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        @rmdir($directory);
    }
}
