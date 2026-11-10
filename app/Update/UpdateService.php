<?php
declare(strict_types=1);

namespace Robo\Update;

/**
 * Applies a verified release with a staging area and a restore-on-failure journal.
 * Authorization deliberately stays at the HTTP boundary; callers must pass only an administrator flag.
 */
final class UpdateService
{
    private const MAX_ARCHIVE_BYTES = 52428800;

    public function __construct(private readonly int $timeoutSeconds = 60)
    {
    }

    /** @return array{current:string,latest:string,available:bool,release:ReleaseInfo,checksum_available:bool} */
    public function check(string $projectRoot, GitHubReleaseClient $client): array
    {
        $current = $this->currentVersion($projectRoot);
        $release = $client->latest();

        return ['current' => $current, 'latest' => $release->version, 'available' => Version::compare($release->version, $current) > 0, 'release' => $release, 'checksum_available' => $release->sha256 !== null];
    }

    /**
     * @return array{version:string,changed_files:int,backup:string}
     */
    public function apply(string $projectRoot, ReleaseInfo $release, bool $isAdministrator): array
    {
        if (!$isAdministrator) {
            throw new UpdateException('Aktualizaci systému může spustit pouze správce.');
        }
        $projectRoot = realpath($projectRoot) ?: throw new UpdateException('Kořen systému ROBO neexistuje.');
        $current = $this->currentVersion($projectRoot);
        if (Version::compare($release->version, $current) <= 0) {
            throw new UpdateException('Vybrané vydání není novější než nainstalovaná verze.');
        }
        if ($release->sha256 === null) {
            throw new UpdateException('GitHub u souboru vydání nezveřejnil SHA-256. Aktualizace nebyla provedena.');
        }
        if (!class_exists(\ZipArchive::class)) {
            throw new UpdateException('Server nemá rozšíření ZipArchive potřebné pro bezpečnou aktualizaci.');
        }

        $work = $projectRoot . '/.robo-update-' . bin2hex(random_bytes(12));
        $stage = $work . '/stage';
        $backup = $work . '/backup';
        if (!mkdir($stage, 0700, true) || !mkdir($backup, 0700, true)) {
            throw new UpdateException('Nelze připravit pracovní adresář aktualizace.');
        }
        $journal = ['version' => $release->version, 'created_files' => [], 'overwritten_files' => []];
        try {
            $archive = $work . '/robo-update.zip';
            $this->downloadAndVerify($release, $archive);
            $files = $this->extractAndValidate($archive, $stage, $release->version);
            file_put_contents($work . '/journal.json', json_encode($journal, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), LOCK_EX);
            foreach ($files as $relative) {
                $source = $stage . '/' . $relative;
                $target = $projectRoot . '/' . $relative;
                if ($relative === 'config/local.php') {
                    continue;
                }
                $targetDirectory = dirname($target);
                if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0750, true)) {
                    throw new UpdateException('Nelze vytvořit cílový adresář aktualizace.');
                }
                if (is_file($target)) {
                    $backupTarget = $backup . '/' . $relative;
                    if (!is_dir(dirname($backupTarget)) && !mkdir(dirname($backupTarget), 0700, true)) {
                        throw new UpdateException('Nelze vytvořit zálohu aktualizace.');
                    }
                    if (!copy($target, $backupTarget)) {
                        throw new UpdateException('Nelze zálohovat soubor před aktualizací.');
                    }
                    $journal['overwritten_files'][] = $relative;
                } else {
                    $journal['created_files'][] = $relative;
                }
                $temporary = $target . '.robo-new-' . bin2hex(random_bytes(6));
                if (!copy($source, $temporary) || !rename($temporary, $target)) {
                    @unlink($temporary);
                    throw new UpdateException('Nelze bezpečně zapsat soubor aktualizace.');
                }
                file_put_contents($work . '/journal.json', json_encode($journal, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), LOCK_EX);
            }
            file_put_contents($projectRoot . '/VERSION', $release->version . PHP_EOL, LOCK_EX);

            return ['version' => $release->version, 'changed_files' => count($files), 'backup' => $backup];
        } catch (\Throwable $exception) {
            $this->rollback($projectRoot, $backup, $journal);
            throw $exception instanceof UpdateException ? $exception : new UpdateException('Aktualizace selhala a změněné soubory byly obnoveny.', 0, $exception);
        } finally {
            $this->deleteTree($stage);
        }
    }

    public static function assertSafeArchivePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)) {
            throw new UpdateException('Archiv obsahuje nepovolenou cestu.');
        }
        $parts = explode('/', $path);
        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..' || str_contains($part, "\0")) {
                throw new UpdateException('Archiv obsahuje nebezpečnou cestu.');
            }
        }
        if ($parts[0] === '.git' || $path === 'config/local.php') {
            throw new UpdateException('Archiv se pokouší přepsat chráněná data.');
        }

        return implode('/', $parts);
    }

    private function currentVersion(string $projectRoot): string
    {
        $file = $projectRoot . '/VERSION';
        if (!is_file($file)) {
            throw new UpdateException('Chybí soubor VERSION s nainstalovanou verzí.');
        }

        return Version::normalize((string) file_get_contents($file));
    }

    private function downloadAndVerify(ReleaseInfo $release, string $destination): void
    {
        if (!function_exists('curl_init')) {
            throw new UpdateException('Server nemá rozšíření cURL potřebné pro stažení aktualizace.');
        }
        $out = fopen($destination, 'xb');
        if ($out === false) {
            throw new UpdateException('Nelze vytvořit soubor aktualizace.');
        }
        $handle = curl_init($release->downloadUrl);
        if ($handle === false) {
            fclose($out);
            throw new UpdateException('Nelze vytvořit bezpečné spojení pro aktualizaci.');
        }
        $received = 0;
        curl_setopt_array($handle, [
            CURLOPT_FILE => $out,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => min($this->timeoutSeconds, 20),
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/octet-stream', 'User-Agent: pihrt-com-robo-updater'],
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => static function (int $downloadSize, int $downloaded) use (&$received): int {
                $received = $downloaded;
                return $downloaded > self::MAX_ARCHIVE_BYTES ? 1 : 0;
            },
        ]);
        $success = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        fclose($out);
        if ($success !== true || $status < 200 || $status >= 300 || $received > self::MAX_ARCHIVE_BYTES) {
            @unlink($destination);
            throw new UpdateException('Stažení aktualizace selhalo' . ($error !== '' ? ': ' . $error : '.'));
        }
        $actual = hash_file('sha256', $destination);
        if (!is_string($actual) || !hash_equals($release->sha256 ?? '', strtolower($actual))) {
            @unlink($destination);
            throw new UpdateException('SHA-256 staženého souboru neodpovídá vydání na GitHubu.');
        }
    }

    /** @return list<string> */
    private function extractAndValidate(string $archive, string $stage, string $expectedVersion): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($archive, \ZipArchive::RDONLY) !== true) {
            throw new UpdateException('Stažený soubor není platný ZIP archiv.');
        }
        $files = [];
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = is_array($stat) ? (string) ($stat['name'] ?? '') : '';
                if (str_ends_with($name, '/')) {
                    continue;
                }
                $relative = self::assertSafeArchivePath($name);
                if (!$zip->extractTo($stage, [$name])) {
                    throw new UpdateException('Nelze rozbalit ověřený soubor aktualizace.');
                }
                $files[] = $relative;
            }
        } finally {
            $zip->close();
        }
        if (!in_array('VERSION', $files, true)) {
            throw new UpdateException('Archiv aktualizace neobsahuje soubor VERSION.');
        }
        $archiveVersion = Version::normalize((string) file_get_contents($stage . '/VERSION'));
        if ($archiveVersion !== $expectedVersion) {
            throw new UpdateException('Verze uvnitř archivu neodpovídá verzi vydání na GitHubu.');
        }

        return $files;
    }

    /** @param array{created_files:list<string>,overwritten_files:list<string>} $journal */
    private function rollback(string $projectRoot, string $backup, array $journal): void
    {
        foreach (array_reverse($journal['created_files']) as $relative) {
            @unlink($projectRoot . '/' . $relative);
        }
        foreach ($journal['overwritten_files'] as $relative) {
            $source = $backup . '/' . $relative;
            if (is_file($source)) {
                @copy($source, $projectRoot . '/' . $relative);
            }
        }
    }

    private function deleteTree(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($directory);
    }
}
