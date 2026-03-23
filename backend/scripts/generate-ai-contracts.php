<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$backendApp = $root . '/app';
$panelDir = dirname($root) . '/panel';

$resourceBase = $backendApp . '/Http/Resources';
$requestBase = $backendApp . '/Http/Requests';
$domainBase = $backendApp . '/Domain';

$opts = getopt('', ['dry-run', 'only:']);
$dryRun = isset($opts['dry-run']);
$onlyRaw = strtolower((string)($opts['only'] ?? 'resources,dtos,types'));
$onlyParts = array_filter(array_map('trim', explode(',', $onlyRaw)));
$enabled = [
    'resources' => in_array('resources', $onlyParts, true),
    'dtos' => in_array('dtos', $onlyParts, true),
    'types' => in_array('types', $onlyParts, true),
];

if (!$enabled['resources'] && !$enabled['dtos'] && !$enabled['types']) {
    fwrite(STDERR, "Nothing selected. Use --only=resources,dtos,types\n");
    exit(1);
}

function readPhpFiles(string $dir): array
{
    if (!is_dir($dir)) return [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($rii as $file) {
        if (!$file->isFile()) continue;
        if (substr($file->getFilename(), -4) !== '.php') continue;
        $files[] = $file->getPathname();
    }
    sort($files);
    return $files;
}

function classFromPath(string $path): string
{
    return basename($path, '.php');
}

function parseModelNamespaceAndClass(string $file): array
{
    $src = file_get_contents($file) ?: '';
    preg_match('/namespace\\s+([^;]+);/m', $src, $ns);
    preg_match('/class\\s+(\\w+)/m', $src, $cl);
    return [
        'namespace' => $ns[1] ?? '',
        'class' => $cl[1] ?? classFromPath($file),
        'source' => $src,
    ];
}

function parseFillable(string $src): array
{
    if (!preg_match('/\\$fillable\\s*=\\s*\\[(.*?)\\];/s', $src, $m)) {
        return [];
    }
    preg_match_all('/["\']([a-zA-Z0-9_]+)["\']/', $m[1], $matches);
    return array_values(array_unique($matches[1] ?? []));
}

function parseRules(string $src): array
{
    if (!preg_match('/public\\s+function\\s+rules\\s*\\(\\)\\s*:\\s*array\\s*\\{(.*?)\\n\\s*\\}/s', $src, $m)) {
        return [];
    }

    $body = $m[1];
    $rules = [];
    preg_match_all('/["\']([a-zA-Z0-9_\\.]+)["\']\\s*=>\\s*\\[(.*?)\\],/s', $body, $pairs, PREG_SET_ORDER);

    foreach ($pairs as $pair) {
        $field = $pair[1];
        $ruleRaw = $pair[2];
        preg_match_all('/["\']([^"\']+)["\']/', $ruleRaw, $r);
        $rules[$field] = $r[1] ?? [];
    }

    return $rules;
}

function snakeToCamel(string $field): string
{
    $parts = explode('_', $field);
    $first = array_shift($parts);
    $rest = array_map(static fn(string $p): string => ucfirst($p), $parts);
    return $first . implode('', $rest);
}

function inferPhpType(array $rules): string
{
    foreach ($rules as $rule) if ($rule === 'array') return 'array';
    foreach ($rules as $rule) if ($rule === 'boolean') return 'bool';
    foreach ($rules as $rule) if ($rule === 'integer') return 'int';
    foreach ($rules as $rule) if ($rule === 'numeric') return 'float';
    return 'string';
}

function inferTsType(array $rules): string
{
    foreach ($rules as $rule) if ($rule === 'array') return 'unknown[]';
    foreach ($rules as $rule) if ($rule === 'boolean') return 'boolean';
    foreach ($rules as $rule) if ($rule === 'integer' || $rule === 'numeric') return 'number';
    return 'string';
}

function isNullable(array $rules): bool
{
    return in_array('nullable', $rules, true);
}

function isRequired(array $rules): bool
{
    foreach ($rules as $rule) {
        if (str_starts_with($rule, 'required')) return true;
    }
    return false;
}

function requestDomainFromPath(string $path): string
{
    $relative = str_replace('\\\\', '/', $path);
    if (!preg_match('#/Http/Requests/([^/]+)/#', $relative, $m)) {
        return 'Shared';
    }
    return $m[1];
}

function ensureDir(string $dir, bool $dryRun): void
{
    if ($dryRun) return;
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function hasDtoMethod(string $src): bool
{
    return str_contains($src, 'function dto(');
}

function requestDtoClassFromRequestClass(string $requestClass): string
{
    return preg_replace('/Request$/', 'Dto', $requestClass) ?: ($requestClass . 'Dto');
}

function writeFile(string $path, string $content, bool $dryRun): void
{
    if ($dryRun) return;
    file_put_contents($path, $content);
}

$generatedResources = [];
$generatedDtos = [];
$patchedRequests = [];
$dtoMetaForTs = [];

if ($enabled['resources']) {
    $resourceFiles = readPhpFiles($resourceBase);
    $resourceClassBases = [];
    foreach ($resourceFiles as $rf) {
        $resourceClassBases[] = preg_replace('/Resource$/', '', classFromPath($rf));
    }
    $resourceClassBases = array_values(array_unique($resourceClassBases));

    $modelFiles = array_merge(readPhpFiles($backendApp . '/Models'), readPhpFiles($domainBase));
    $modelFiles = array_values(array_filter($modelFiles, static fn(string $f): bool => str_contains($f, '/Models/')));

    foreach ($modelFiles as $mf) {
        $meta = parseModelNamespaceAndClass($mf);
        $class = $meta['class'];
        $ns = $meta['namespace'];

        if (in_array($class, $resourceClassBases, true)) continue;
        if ($class === 'OrderStatusHistory' && in_array('StatusHistory', $resourceClassBases, true)) continue;

        $folder = 'Shared';
        if (preg_match('/App\\\\Domain\\\\([^\\\\]+)\\\\Models/', $ns, $dm)) {
            $folder = $dm[1];
        } elseif ($ns === 'App\\\\Models') {
            $folder = 'Core';
        }

        $resourceClass = $class . 'Resource';
        $targetDir = $resourceBase . '/' . $folder;
        $target = $targetDir . '/' . $resourceClass . '.php';

        $fillable = parseFillable($meta['source']);
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            'namespace App\\Http\\Resources\\' . $folder . ';',
            '',
            'use Illuminate\\Http\\Request;',
            'use Illuminate\\Http\\Resources\\Json\\JsonResource;',
            '',
            '/**',
            ' * API resource for ' . $class . '.',
            ' *',
            ' * @mixin \\' . $ns . '\\' . $class,
            ' */',
            'class ' . $resourceClass . ' extends JsonResource',
            '{',
            '    /**',
            '     * Transform the resource into an array.',
            '     *',
            '     * @param  Request  $request',
            '     * @return array<string,mixed>',
            '     */',
            '    public function toArray(Request $request): array',
            '    {',
            '        return [',
            "            'id' => \$this->id,",
        ];

        foreach ($fillable as $f) {
            $lines[] = "            '{$f}' => \$this->{$f},";
        }

        $lines[] = "            'created_at' => \$this->created_at?->toIso8601String(),";
        $lines[] = "            'updated_at' => \$this->updated_at?->toIso8601String(),";
        $lines[] = "            'deleted_at' => \$this->deleted_at?->toIso8601String(),";
        $lines[] = '        ];';
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';

        ensureDir($targetDir, $dryRun);
        writeFile($target, implode("\n", $lines), $dryRun);
        $generatedResources[] = $target;
    }
}

if ($enabled['dtos'] || $enabled['types']) {
    $requestFiles = readPhpFiles($requestBase);

    foreach ($requestFiles as $reqFile) {
        $src = file_get_contents($reqFile) ?: '';
        $requestClass = classFromPath($reqFile);
        $dtoClass = requestDtoClassFromRequestClass($requestClass);
        $domain = requestDomainFromPath($reqFile);
        $dtoDir = $domainBase . '/' . $domain . '/Data';
        $dtoPath = $dtoDir . '/' . $dtoClass . '.php';

        $rules = parseRules($src);
        if ($rules === []) continue;

        $fields = [];
        foreach ($rules as $field => $ruleList) {
            if (str_contains($field, '.')) continue;

            $fields[] = [
                'field' => $field,
                'prop' => snakeToCamel($field),
                'phpType' => inferPhpType($ruleList),
                'tsType' => inferTsType($ruleList),
                'nullable' => isNullable($ruleList),
                'required' => isRequired($ruleList),
            ];
        }

        if ($enabled['dtos'] && !file_exists($dtoPath)) {
            ensureDir($dtoDir, $dryRun);

            $lines = [
                '<?php',
                '',
                'declare(strict_types=1);',
                '',
                'namespace App\\Domain\\' . $domain . '\\Data;',
                '',
                '/**',
                ' * Auto-generated DTO for ' . $requestClass . '.',
                ' * Source of truth: validation rules in ' . $requestClass . '.',
                ' */',
                'readonly class ' . $dtoClass,
                '{',
                '    public function __construct(',
            ];

            foreach ($fields as $idx => $f) {
                $phpType = $f['phpType'];
                $nullable = $f['nullable'] || !$f['required'];
                $typeDecl = $nullable && $phpType !== 'array' ? '?' . $phpType : $phpType;
                $default = '';
                if ($phpType === 'array') {
                    $default = ' = []';
                } elseif ($nullable) {
                    $default = ' = null';
                }
                $comma = $idx === count($fields) - 1 ? '' : ',';
                $lines[] = '        public ' . $typeDecl . ' $' . $f['prop'] . $default . $comma;
            }

            $lines[] = '    ) {}';
            $lines[] = '';
            $lines[] = '    /**';
            $lines[] = '     * @param  array<string,mixed>  $data';
            $lines[] = '     */';
            $lines[] = '    public static function fromArray(array $data): self';
            $lines[] = '    {';
            $lines[] = '        return new self(';

            foreach ($fields as $idx => $f) {
                $key = $f['field'];
                $prop = $f['prop'];
                $phpType = $f['phpType'];
                $nullable = $f['nullable'] || !$f['required'];

                if ($phpType === 'array') {
                    $rhs = '$data[\'' . $key . '\'] ?? []';
                } elseif ($nullable) {
                    $rhs = '$data[\'' . $key . '\'] ?? null';
                } else {
                    $rhs = '$data[\'' . $key . '\']';
                }

                $comma = $idx === count($fields) - 1 ? '' : ',';
                $lines[] = '            ' . $prop . ': ' . $rhs . $comma;
            }

            $lines[] = '        );';
            $lines[] = '    }';
            $lines[] = '}';
            $lines[] = '';

            writeFile($dtoPath, implode("\n", $lines), $dryRun);
            $generatedDtos[] = $dtoPath;
        }

        if ($enabled['dtos'] && !hasDtoMethod($src)) {
            $dtoUse = 'use App\\Domain\\' . $domain . '\\Data\\' . $dtoClass . ';';
            if (!str_contains($src, $dtoUse)) {
                $src = preg_replace('/(namespace\\s+[^;]+;\\n)/', "$1\n" . $dtoUse . "\n", $src, 1) ?? $src;
            }

            $method = "\n    /**\n     * Build the DTO from validated request data.\n     */\n    public function dto(): {$dtoClass}\n    {\n        return {$dtoClass}::fromArray(\$this->validated());\n    }\n";
            $src = preg_replace('/\\n\\}\\s*$/', $method . "\n}\n", $src, 1) ?? $src;
            writeFile($reqFile, $src, $dryRun);
            $patchedRequests[] = $reqFile;
        }

        $dtoMetaForTs[] = [
            'dtoClass' => $dtoClass,
            'fields' => $fields,
        ];
    }
}

$tsPath = $panelDir . '/app/types/backend-contracts.generated.ts';
if ($enabled['types']) {
    $ts = [];
    $ts[] = '/**';
    $ts[] = ' * Auto-generated from backend Requests/DTO conventions.';
    $ts[] = ' * Do not edit manually. Run backend/scripts/generate-ai-contracts.php';
    $ts[] = ' */';
    $ts[] = '';

    usort($dtoMetaForTs, static fn(array $a, array $b): int => strcmp($a['dtoClass'], $b['dtoClass']));

    foreach ($dtoMetaForTs as $dto) {
        $ts[] = 'export interface ' . $dto['dtoClass'] . ' {';
        foreach ($dto['fields'] as $field) {
            $optional = (!$field['required']) || $field['nullable'];
            $type = $field['tsType'] . (($field['nullable'] && $field['tsType'] !== 'unknown[]') ? ' | null' : '');
            $mark = $optional ? '?' : '';
            $ts[] = '  ' . $field['field'] . $mark . ': ' . $type . ';';
        }
        $ts[] = '}';
        $ts[] = '';
    }

    ensureDir(dirname($tsPath), $dryRun);
    writeFile($tsPath, implode("\n", $ts), $dryRun);
}

fwrite(STDOUT, 'Mode: ' . ($dryRun ? 'dry-run' : 'write') . PHP_EOL);
fwrite(STDOUT, 'Enabled: ' . implode(',', array_keys(array_filter($enabled))) . PHP_EOL);
fwrite(STDOUT, 'Generated resources: ' . count($generatedResources) . PHP_EOL);
fwrite(STDOUT, 'Generated DTOs: ' . count($generatedDtos) . PHP_EOL);
fwrite(STDOUT, 'Patched requests: ' . count($patchedRequests) . PHP_EOL);
if ($enabled['types']) fwrite(STDOUT, 'Generated TS: ' . $tsPath . PHP_EOL);

if ($generatedResources) fwrite(STDOUT, PHP_EOL . 'Resources:' . PHP_EOL . implode(PHP_EOL, $generatedResources) . PHP_EOL);
if ($generatedDtos) fwrite(STDOUT, PHP_EOL . 'DTOs:' . PHP_EOL . implode(PHP_EOL, $generatedDtos) . PHP_EOL);
if ($patchedRequests) fwrite(STDOUT, PHP_EOL . 'Requests:' . PHP_EOL . implode(PHP_EOL, $patchedRequests) . PHP_EOL);
