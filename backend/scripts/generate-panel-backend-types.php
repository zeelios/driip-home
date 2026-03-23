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
$modelInterfaceByFqcn = [];
$modelMetaByFile = [];
foreach ($modelFiles as $file) {
    $meta = parsePhpClassMeta($file);
    $modelMetaByFile[$file] = $meta;
    $classCounts[$meta['class']] = ($classCounts[$meta['class']] ?? 0) + 1;
}

foreach ($modelFiles as $file) {
    $meta = $modelMetaByFile[$file];
    $modelInterfaceByFqcn[$meta['namespace'] . '\\' . $meta['class']] = modelInterfaceName($file, $meta['class'], $classCounts);
}

$modelDefinitions = [];
foreach ($modelFiles as $file) {
    $meta = $modelMetaByFile[$file];
    $interfaceName = modelInterfaceName($file, $meta['class'], $classCounts);
    $parentInterface = null;

    if (!empty($meta['parent'])) {
        $parentFqcn = resolveClassReference(
            $meta['parent'],
            $meta['namespace'],
            parseUseStatements($meta['source'])
        );
        $parentInterface = $modelInterfaceByFqcn[$parentFqcn] ?? null;
    }

    $modelDefinitions[] = [
        'name' => $interfaceName,
        'source' => $file,
        'properties' => parseModelProperties($meta['source']),
        'relations' => parseModelRelations(
            $meta['source'],
            $meta['namespace'],
            $modelInterfaceByFqcn
        ),
        'extends' => $parentInterface,
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
    preg_match('/class\s+\w+\s+extends\s+([^\s{]+)/m', $source, $parentMatch);

    return [
        'namespace' => $namespaceMatch[1] ?? '',
        'class' => $classMatch[1] ?? basename($file, '.php'),
        'parent' => $parentMatch[1] ?? null,
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

function parseModelRelations(string $source, string $namespace, array $modelInterfaceByFqcn): array
{
    $imports = parseUseStatements($source);
    $relations = [];

    preg_match_all(
        '/public\s+function\s+([a-zA-Z0-9_]+)\s*\([^)]*\)\s*:\s*[^{]+\{(?:(?!public\s+function).)*?return\s+\$this->(belongsTo|hasOne|hasMany|belongsToMany|morphOne|morphMany|morphToMany|morphedByMany)\(\s*(?:([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)::class|\'([^\']+)\'|"([^"]+)")/s',
        $source,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {
        $relationName = $match[1];
        $relationKind = $match[2];
        $classReference = $match[3] ?: ($match[4] ?: $match[5]);
        $fqcn = resolveClassReference($classReference, $namespace, $imports);
        $targetType = modelTsTypeForClass($fqcn, $modelInterfaceByFqcn);

        $relations[$relationName] = [
            'name' => $relationName,
            'type' => isCollectionRelation($relationKind)
                ? ($targetType . '[]')
                : ($targetType . ' | null'),
        ];
    }

    if (str_contains($source, 'HasRoles')) {
        $relations['roles'] = [
            'name' => 'roles',
            'type' => 'RoleModel[]',
        ];
        $relations['permissions'] = [
            'name' => 'permissions',
            'type' => 'PermissionModel[]',
        ];
    }

    return array_values($relations);
}

function parseUseStatements(string $source): array
{
    preg_match_all('/^use\s+([^;]+);/m', $source, $matches, PREG_SET_ORDER);

    $imports = [];
    foreach ($matches as $match) {
        $fqcn = trim($match[1]);
        $alias = basename(str_replace('\\', '/', $fqcn));
        $imports[$alias] = $fqcn;
    }

    return $imports;
}

function resolveClassReference(string $classReference, string $namespace, array $imports): string
{
    $normalized = trim($classReference, " \t\n\r\0\x0B\\");

    if ($normalized === '') {
        return '';
    }

    if (str_contains($normalized, '\\')) {
        return ltrim($normalized, '\\');
    }

    if (isset($imports[$normalized])) {
        return $imports[$normalized];
    }

    return $namespace . '\\' . $normalized;
}

function modelTsTypeForClass(string $fqcn, array $modelInterfaceByFqcn): string
{
    if (isset($modelInterfaceByFqcn[$fqcn])) {
        return $modelInterfaceByFqcn[$fqcn];
    }

    return basename(str_replace('\\', '/', $fqcn)) . 'Model';
}

function isCollectionRelation(string $relationKind): bool
{
    return in_array($relationKind, ['hasMany', 'belongsToMany', 'morphMany', 'morphToMany', 'morphedByMany'], true);
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
        ' * Source: backend/app/Models and backend/app/Domain/[domain]/Models',
        ' */',
        '',
        'export interface RoleModel {',
        '  id?: string;',
        '  name?: string;',
        '  guard_name?: string;',
        '  [key: string]: unknown;',
        '}',
        '',
        'export interface PermissionModel {',
        '  id?: string;',
        '  name?: string;',
        '  guard_name?: string;',
        '  [key: string]: unknown;',
        '}',
        '',
    ];

    foreach ($models as $model) {
        $extends = !empty($model['extends']) ? ' extends ' . $model['extends'] : '';
        $lines[] = 'export interface ' . $model['name'] . $extends . ' {';
        foreach ($model['properties'] as $property) {
            $lines[] = '  ' . $property['name'] . ': ' . $property['type'] . ';';
        }
        if (!empty($model['relations'])) {
            $lines[] = '';
            $lines[] = '  // relations';
            foreach ($model['relations'] as $relation) {
                $lines[] = '  ' . $relation['name'] . '?: ' . $relation['type'] . ';';
            }
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
