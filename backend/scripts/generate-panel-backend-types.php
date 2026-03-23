<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$backendApp = $root . '/app';
$panelTypesDir = dirname($root) . '/panel/types/generated';

ensureDir($panelTypesDir);

$modelFiles = array_merge(
    findPhpFiles($backendApp . '/Models'),
    findPhpFiles($backendApp . '/Domain')
);
$modelFiles = array_values(array_filter(
    $modelFiles,
    static fn(string $path): bool => str_contains($path, '/Models/')
));
sort($modelFiles);

$requestFiles = findPhpFiles($backendApp . '/Http/Requests');
sort($requestFiles);

$classCounts = [];
foreach ($modelFiles as $file) {
    $meta = parsePhpClassMeta($file);
    $classCounts[$meta['class']] = ($classCounts[$meta['class']] ?? 0) + 1;
}

$modelDefinitions = [];
foreach ($modelFiles as $file) {
    $meta = parsePhpClassMeta($file);
    $modelDefinitions[] = [
        'name' => modelInterfaceName($file, $meta['class'], $classCounts),
        'source' => $file,
        'properties' => parseModelProperties($meta['source']),
    ];
}

$dtoDefinitions = [];
foreach ($requestFiles as $file) {
    $requestClass = basename($file, '.php');
    $dtoDefinitions[] = [
        'name' => preg_replace('/Request$/', 'Dto', $requestClass) ?: ($requestClass . 'Dto'),
        'source' => $file,
        'fields' => parseRequestFields(file_get_contents($file) ?: ''),
    ];
}

$modelsOutput = buildModelsTs($modelDefinitions);
$dtosOutput = buildDtosTs($dtoDefinitions);
$indexOutput = <<<TS
export * from "./backend-models.generated";
export * from "./backend-dtos.generated";
TS;

file_put_contents($panelTypesDir . '/backend-models.generated.ts', $modelsOutput);
file_put_contents($panelTypesDir . '/backend-dtos.generated.ts', $dtosOutput);
file_put_contents($panelTypesDir . '/index.ts', $indexOutput . PHP_EOL);

fwrite(STDOUT, "Generated " . count($modelDefinitions) . " model interfaces\n");
fwrite(STDOUT, "Generated " . count($dtoDefinitions) . " DTO interfaces\n");

function ensureDir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function findPhpFiles(string $dir): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    $files = [];
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        if (!str_ends_with($file->getFilename(), '.php')) {
            continue;
        }
        $files[] = $file->getPathname();
    }

    return $files;
}

function parsePhpClassMeta(string $file): array
{
    $source = file_get_contents($file) ?: '';
    preg_match('/namespace\s+([^;]+);/m', $source, $namespaceMatch);
    preg_match('/class\s+(\w+)/m', $source, $classMatch);

    return [
        'namespace' => $namespaceMatch[1] ?? '',
        'class' => $classMatch[1] ?? basename($file, '.php'),
        'source' => $source,
    ];
}

function modelInterfaceName(string $file, string $class, array $classCounts): string
{
    if (($classCounts[$class] ?? 0) === 1) {
        return $class . 'Model';
    }

    if (preg_match('#/Domain/([^/]+)/Models/#', $file, $match)) {
        return $match[1] . $class . 'Model';
    }

    return 'App' . $class . 'Model';
}

function parseModelProperties(string $source): array
{
    preg_match_all('/@property\s+([^\s]+)\s+\$([a-zA-Z0-9_]+)/', $source, $matches, PREG_SET_ORDER);

    $properties = [];
    foreach ($matches as $match) {
        $properties[] = [
            'name' => $match[2],
            'type' => phpDocTypeToTs($match[1]),
        ];
    }

    return $properties;
}

function phpDocTypeToTs(string $type): string
{
    $normalized = trim($type);

    if (str_contains($normalized, '|')) {
        $parts = array_map('trim', explode('|', $normalized));
        $mapped = array_map('phpDocTypeToTs', $parts);
        $deduped = [];
        foreach ($mapped as $mappedType) {
            foreach (explode(' | ', $mappedType) as $piece) {
                $piece = trim($piece);
                if ($piece === '') {
                    continue;
                }
                $deduped[$piece] = true;
            }
        }
        return implode(' | ', array_keys($deduped));
    }

    if (preg_match('/^array<.*>$/', $normalized)) {
        return 'unknown[]';
    }

    if ($normalized === 'array') {
        return 'unknown[]';
    }

    if (preg_match('/^bool(ean)?$/', $normalized)) {
        return 'boolean';
    }

    if (preg_match('/^(int|float|double)$/', $normalized)) {
        return 'number';
    }

    if ($normalized === 'string') {
        return 'string';
    }

    if ($normalized === 'mixed') {
        return 'unknown';
    }

    if ($normalized === 'null') {
        return 'null';
    }

    if (str_contains($normalized, '\\Carbon\\Carbon')) {
        return 'string';
    }

    return 'string';
}

function parseRequestFields(string $source): array
{
    if (!preg_match('/public\s+function\s+rules\s*\(\)\s*:\s*array\s*\{(.*?)\n\s*\}/s', $source, $match)) {
        return [];
    }

    $body = $match[1];
    preg_match_all('/[\"\']([a-zA-Z0-9_\.]+)[\"\']\s*=>\s*\[(.*?)\],/s', $body, $pairs, PREG_SET_ORDER);

    $fields = [];
    foreach ($pairs as $pair) {
        $field = $pair[1];
        if (str_contains($field, '.')) {
            continue;
        }

        preg_match_all('/[\"\']([^\"\']+)[\"\']/', $pair[2], $ruleMatches);
        $rules = $ruleMatches[1] ?? [];

        $fields[] = [
            'name' => $field,
            'type' => requestRulesToTs($rules),
            'optional' => !isRequiredRuleSet($rules),
        ];
    }

    return $fields;
}

function isRequiredRuleSet(array $rules): bool
{
    foreach ($rules as $rule) {
        if (str_starts_with($rule, 'required')) {
            return true;
        }
    }

    return false;
}

function requestRulesToTs(array $rules): string
{
    $nullable = in_array('nullable', $rules, true);
    $baseType = 'string';

    foreach ($rules as $rule) {
        if ($rule === 'array') {
            $baseType = 'unknown[]';
            break;
        }
        if ($rule === 'boolean') {
            $baseType = 'boolean';
            break;
        }
        if ($rule === 'integer' || $rule === 'numeric') {
            $baseType = 'number';
            break;
        }
    }

    return $nullable ? ($baseType . ' | null') : $baseType;
}

function buildModelsTs(array $models): string
{
    $lines = [
        '/**',
        ' * Auto-generated from backend model PHPDoc property annotations.',
        ' * Source: backend/app/Models and backend/app/Domain/*/Models',
        ' */',
        '',
    ];

    foreach ($models as $model) {
        $lines[] = 'export interface ' . $model['name'] . ' {';
        foreach ($model['properties'] as $property) {
            $lines[] = '  ' . $property['name'] . ': ' . $property['type'] . ';';
        }
        $lines[] = '}';
        $lines[] = '';
    }

    return implode(PHP_EOL, $lines);
}

function buildDtosTs(array $dtos): string
{
    $lines = [
        '/**',
        ' * Auto-generated from backend FormRequest validation rules.',
        ' * Source: backend/app/Http/Requests',
        ' */',
        '',
    ];

    foreach ($dtos as $dto) {
        $lines[] = 'export interface ' . $dto['name'] . ' {';
        foreach ($dto['fields'] as $field) {
            $optional = $field['optional'] ? '?' : '';
            $lines[] = '  ' . $field['name'] . $optional . ': ' . $field['type'] . ';';
        }
        $lines[] = '}';
        $lines[] = '';
    }

    return implode(PHP_EOL, $lines);
}
