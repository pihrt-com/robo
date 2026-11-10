<?php
declare(strict_types=1);

namespace Robo\Update;

final class ReleaseInfo
{
    public function __construct(
        public readonly string $tag,
        public readonly string $version,
        public readonly string $publishedAt,
        public readonly string $downloadUrl,
        public readonly ?string $sha256,
        public readonly string $releaseUrl,
    ) {
    }
}
