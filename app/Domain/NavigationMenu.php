<?php
declare(strict_types=1);

namespace Robo\Domain;

final class NavigationMenu
{
    /**
     * @param list<string> $permissions
     * @param array<string, string> $labels
     * @return list<array{path:string,label:string}>
     */
    public static function items(string $roleCode, array $permissions, array $labels): array
    {
        $definitions = [
            ['path' => 'technical', 'key' => 'technical', 'permission' => 'technical.view', 'fallback' => 'TECHNICKÁ'],
            ['path' => 'track-1', 'key' => 'track_1', 'permission' => 'track_1.view', 'fallback' => 'DRÁHA 1'],
            ['path' => 'track-2', 'key' => 'track_2', 'permission' => 'track_2.view', 'fallback' => 'DRÁHA 2'],
            ['path' => 'standings', 'key' => 'standings', 'permission' => 'results.view', 'fallback' => 'POŘADÍ'],
            ['path' => 'system', 'key' => 'system', 'permission' => 'system.view', 'fallback' => 'SYSTÉM'],
        ];
        $administrator = $roleCode === 'administrator' || in_array('*', $permissions, true) || in_array('system.view', $permissions, true);
        $items = [];
        foreach ($definitions as $definition) {
            if (!$administrator && !in_array($definition['permission'], $permissions, true)) {
                continue;
            }
            $label = trim((string) ($labels[$definition['key']] ?? ''));
            $items[] = ['path' => $definition['path'], 'label' => $label !== '' ? $label : $definition['fallback']];
        }

        return $items;
    }
}
