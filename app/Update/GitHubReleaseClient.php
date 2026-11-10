<?php
declare(strict_types=1);

namespace Robo\Update;

/** Read-only client for the public ROBO release feed. */
final class GitHubReleaseClient
{
    private const API_URL = 'https://api.github.com/repos/pihrt-com/robo/releases/latest';
    private const ASSET_NAME = 'robo-update.zip';

    public function __construct(private readonly int $timeoutSeconds = 15)
    {
        if ($timeoutSeconds < 3 || $timeoutSeconds > 60) {
            throw new \InvalidArgumentException('Timeout musí být mezi 3 a 60 sekundami.');
        }
    }

    public function latest(): ReleaseInfo
    {
        $json = $this->httpsGet(self::API_URL, 'application/vnd.github+json');
        try {
            $release = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new UpdateException('GitHub vrátil neplatnou odpověď o vydání.', 0, $exception);
        }
        if (!is_array($release) || !is_string($release['tag_name'] ?? null)) {
            throw new UpdateException('GitHub nevrátil platné nejnovější vydání ROBO.');
        }

        $asset = null;
        foreach (($release['assets'] ?? []) as $candidate) {
            if (is_array($candidate) && ($candidate['name'] ?? null) === self::ASSET_NAME) {
                $asset = $candidate;
                break;
            }
        }
        if (!is_array($asset) || !is_string($asset['browser_download_url'] ?? null)) {
            throw new UpdateException('Nejnovější vydání neobsahuje požadovaný soubor robo-update.zip.');
        }

        $url = $asset['browser_download_url'];
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ($host !== 'github.com' && !str_ends_with($host, '.github.com'))) {
            throw new UpdateException('Adresa aktualizačního souboru není důvěryhodná HTTPS adresa GitHubu.');
        }
        $digest = $asset['digest'] ?? null;
        $sha256 = null;
        if (is_string($digest) && preg_match('/^sha256:([a-f0-9]{64})$/Di', $digest, $match)) {
            $sha256 = strtolower($match[1]);
        }

        return new ReleaseInfo(
            (string) $release['tag_name'],
            Version::normalize((string) $release['tag_name']),
            is_string($release['published_at'] ?? null) ? $release['published_at'] : '',
            $url,
            $sha256,
            is_string($release['html_url'] ?? null) ? $release['html_url'] : 'https://github.com/pihrt-com/robo/releases',
        );
    }

    private function httpsGet(string $url, string $accept): string
    {
        if (!function_exists('curl_init')) {
            throw new UpdateException('Server nemá rozšíření cURL potřebné pro bezpečné ověření vydání.');
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new UpdateException('Nelze vytvořit bezpečné spojení s GitHubem.');
        }
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: ' . $accept, 'User-Agent: pihrt-com-robo-updater'],
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if (!is_string($body) || $status < 200 || $status >= 300) {
            throw new UpdateException('GitHub nelze ověřit' . ($error !== '' ? ': ' . $error : '.'));
        }

        return $body;
    }
}
