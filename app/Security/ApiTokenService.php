<?php
declare(strict_types=1);

namespace Robo\Security;

use PDO;

final class ApiTokenService
{
    private const HASH_KEY = 'playoff_api_token_hash';
    private const CREATED_KEY = 'playoff_api_token_created_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function generate(): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $save = $this->pdo->prepare('INSERT INTO app_settings (setting_key,setting_value) VALUES (:key,:value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $save->execute(['key' => self::HASH_KEY, 'value' => self::hash($token)]);
        $save->execute(['key' => self::CREATED_KEY, 'value' => gmdate(DATE_ATOM)]);
        return $token;
    }

    public function revoke(): void
    {
        $delete = $this->pdo->prepare('DELETE FROM app_settings WHERE setting_key IN (:hash_key,:created_key)');
        $delete->execute(['hash_key' => self::HASH_KEY, 'created_key' => self::CREATED_KEY]);
    }

    /** @return array{enabled:bool,created_at:?string} */
    public function status(): array
    {
        $query = $this->pdo->prepare('SELECT setting_key,setting_value FROM app_settings WHERE setting_key IN (:hash_key,:created_key)');
        $query->execute(['hash_key' => self::HASH_KEY, 'created_key' => self::CREATED_KEY]);
        $values = $query->fetchAll(PDO::FETCH_KEY_PAIR);
        return [
            'enabled' => isset($values[self::HASH_KEY]) && is_string($values[self::HASH_KEY]) && $values[self::HASH_KEY] !== '',
            'created_at' => isset($values[self::CREATED_KEY]) ? (string) $values[self::CREATED_KEY] : null,
        ];
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
}
