<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Scout\Searchable;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Import all searchable models into the search index.
 *
 * This command finds all models using the Searchable trait
 * and runs scout:import for each one.
 */
class ScoutImportAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:import-all
                            {--model= : Import only a specific model (full class name)}
                            {--fresh : Flush all indexes before importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all searchable models into Meilisearch';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Import specific model if provided
        if ($this->option('model')) {
            return $this->importModel($this->option('model'));
        }

        // Find all searchable models
        $models = $this->getSearchableModels();

        if (empty($models)) {
            $this->warn('No searchable models found.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($models) . ' searchable model(s):');
        foreach ($models as $model) {
            $this->line('  - ' . $model);
        }
        $this->newLine();

        // Flush indexes if --fresh flag is set
        if ($this->option('fresh')) {
            $this->info('Flushing all indexes...');
            foreach ($models as $model) {
                $this->call('scout:flush', ['model' => $model]);
            }
            $this->newLine();
        }

        // Import each model
        $successCount = 0;
        $failCount = 0;

        foreach ($models as $model) {
            $shortName = class_basename($model);
            $this->info("Importing [{$shortName}]...");

            $result = $this->call('scout:import', ['model' => $model]);

            if ($result === self::SUCCESS) {
                $successCount++;
            } else {
                $failCount++;
                $this->error("Failed to import [{$shortName}]");
            }

            $this->newLine();
        }

        // Summary
        $this->info('Import complete!');
        $this->line("  Successful: {$successCount}");
        $this->line("  Failed: {$failCount}");

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Import a specific model.
     */
    private function importModel(string $modelClass): int
    {
        if (!class_exists($modelClass)) {
            $this->error("Model [{$modelClass}] does not exist.");
            return self::FAILURE;
        }

        if (!$this->classUsesTrait($modelClass, Searchable::class)) {
            $this->warn("Model [{$modelClass}] does not use Searchable trait.");
        }

        return $this->call('scout:import', ['model' => $modelClass]);
    }

    /**
     * Find all models that use the Searchable trait.
     *
     * @return array<string>
     */
    private function getSearchableModels(): array
    {
        $models = [];
        $modelsPath = app_path('Domain');

        if (!is_dir($modelsPath)) {
            $this->warn("Models directory not found: {$modelsPath}");
            return $models;
        }

        $finder = new Finder();
        $finder->files()->in($modelsPath)->name('*.php');

        foreach ($finder as $file) {
            $className = $this->getClassFromFile($file->getRealPath());

            if ($className === null) {
                continue;
            }

            if (!class_exists($className)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($className);

                // Check if it's a Model and uses Searchable trait
                if (
                    $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)
                    && $this->classUsesTrait($className, Searchable::class)
                ) {
                    $models[] = $className;
                }
            } catch (\Throwable $e) {
                // Skip models that can't be reflected
                continue;
            }
        }

        sort($models);

        return $models;
    }

    /**
     * Check if a class uses a specific trait (recursive through parent classes).
     *
     * @param  string  $class
     * @param  string  $trait
     * @return bool
     */
    private function classUsesTrait(string $class, string $trait): bool
    {
        return in_array($trait, class_uses_recursive($class), true);
    }

    /**
     * Extract the fully-qualified class name from a file.
     */
    private function getClassFromFile(string $path): ?string
    {
        $contents = file_get_contents($path);

        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        $class = null;
        if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
            $class = $matches[1];
        }

        if ($class === null) {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }
}
