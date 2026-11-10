<?php
declare(strict_types=1);

namespace Robo\Core;

use RuntimeException;

final class Config
{
    /** @param array<string, mixed> $values */
    private function __construct(private readonly array $values)
    {
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException('Konfigurace systému ROBO zatím neexistuje. Nejprve spusťte instalaci.');
        }

        $values = require $path;
        if (!is_array($values)) {
            throw new RuntimeException('Konfigurační soubor nemá platný formát.');
        }

        return new self($values);
    }

    public function string(string $key): string
    {
        $value = $this->values[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new RuntimeException(sprintf('V konfiguraci chybí položka %s.', $key));
        }

        return $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return isset($this->values[$key]) ? (bool) $this->values[$key] : $default;
    }
}
