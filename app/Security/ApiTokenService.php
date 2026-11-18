<?php
declare(strict_types=1);

namespace Robo\Security;

use PDO;

final class ApiTokenService
{
    private const HASH_KEY = 'playoff_api_token_hash';
    private const CREATED_KEY = 'playoff_api_token_created_at';
    private const CIPHERTEXT_KEY = 'playoff_api_token_ciphertext';
    private const TIME_MODE_KEY = 'playoff_api_time_mode';

    public function __construct(private readonly PDO $pdo, private readonly string $appSecret)
    {
    }

    public function generate(): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $save = $this->pdo->prepare('INSERT INTO app_settings (setting_key,setting_value) VALUES (:key,:value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $save->execute(['key' => self::HASH_KEY, 'value' => self::hash($token)]);
        $save->execute(['key' => self::CIPHERTEXT_KEY, 'value' => $this->encrypt($token)]);
        $save->execute(['key' => self::CREATED_KEY, 'value' => gmdate(DATE_ATOM)]);
        return $token;
    }

    public function revoke(): void
    {
        $delete = $this->pdo->prepare('DELETE FROM app_settings WHERE setting_key IN (:hash_key,:created_key,:ciphertext_key)');
        $delete->execute(['hash_key' => self::HASH_KEY, 'created_key' => self::CREATED_KEY, 'ciphertext_key' => self::CIPHERTEXT_KEY]);
    }

    public function saveTimeMode(string $timeMode): void
    {
        if (!in_array($timeMode, ['driving', 'including_penalties'], true)) {
            throw new \InvalidArgumentException('Neplatné nastavení času pro API.');
        }
        $save = $this->pdo->prepare('INSERT INTO app_settings (setting_key,setting_value) VALUES (:key,:value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $save->execute(['key' => self::TIME_MODE_KEY, 'value' => $timeMode]);
    }

    /** @return array{enabled:bool,created_at:?string,time_mode:string,can_reveal:bool} */
    public function status(): array
    {
        $query = $this->pdo->prepare('SELECT setting_key,setting_value FROM app_settings WHERE setting_key IN (:hash_key,:created_key,:ciphertext_key,:time_mode_key)');
        $query->execute(['hash_key' => self::HASH_KEY, 'created_key' => self::CREATED_KEY, 'ciphertext_key' => self::CIPHERTEXT_KEY, 'time_mode_key' => self::TIME_MODE_KEY]);
        $values = $query->fetchAll(PDO::FETCH_KEY_PAIR);
        $timeMode = (string) ($values[self::TIME_MODE_KEY] ?? 'driving');
        return [
            'enabled' => isset($values[self::HASH_KEY]) && is_string($values[self::HASH_KEY]) && $values[self::HASH_KEY] !== '',
            'created_at' => isset($values[self::CREATED_KEY]) ? (string) $values[self::CREATED_KEY] : null,
            'time_mode' => in_array($timeMode, ['driving', 'including_penalties'], true) ? $timeMode : 'driving',
            'can_reveal' => isset($values[self::CIPHERTEXT_KEY]) && is_string($values[self::CIPHERTEXT_KEY]) && $values[self::CIPHERTEXT_KEY] !== '',
        ];
    }

    public function reveal(): ?string
    {
        $query = $this->pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key=:key');
        $query->execute(['key' => self::CIPHERTEXT_KEY]);
        $ciphertext = $query->fetchColumn();
        return is_string($ciphertext) && $ciphertext !== '' ? $this->decrypt($ciphertext) : null;
    }

    public function verify(string $token): bool
    {
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
            return false;
        }
        $query = $this->pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key=:key');
        $query->execute(['key' => self::HASH_KEY]);
        $stored = $query->fetchColumn();
        return is_string($stored) && strlen($stored) === 64 && hash_equals($stored, self::hash($token));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function encrypt(string $value): string
    {
        if (!function_exists('openssl_encrypt')) {
            throw new \RuntimeException('Server nemá rozšíření OpenSSL potřebné pro bezpečné uložení API klíče.');
        }
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($value, 'aes-256-gcm', hash('sha256', $this->appSecret, true), OPENSSL_RAW_DATA, $iv, $tag);
        if ($encrypted === false) {
            throw new \RuntimeException('API klíč nelze bezpečně uložit.');
        }
        return rtrim(strtr(base64_encode($iv . $tag . $encrypted), '+/', '-_'), '=');
    }

    private function decrypt(string $ciphertext): ?string
    {
        if (!function_exists('openssl_decrypt')) {
            return null;
        }
        $raw = base64_decode(strtr($ciphertext, '-_', '+/'), true);
        if ($raw === false || strlen($raw) < 29) {
            return null;
        }
        $value = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', hash('sha256', $this->appSecret, true), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return is_string($value) && preg_match('/^[A-Za-z0-9_-]{43}$/', $value) === 1 ? $value : null;
    }
}
