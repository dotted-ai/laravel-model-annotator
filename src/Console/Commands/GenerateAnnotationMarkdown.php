<?php

namespace DottedAI\ModelAnnotator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Database\Eloquent\Relations\Relation;

class GenerateAnnotationMarkdown extends Command
{
    protected $signature = 'models:export-docs';
    protected $description = 'Generate a Markdown file with annotations for all Eloquent models';

    public function handle()
    {
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);
        $markdown = "# Eloquent Model Annotations\n\n";

        foreach ($files as $file) {
            $class = $this->getClassFromFile($file);
            if (!class_exists($class)) continue;

            try {
                $reflection = new ReflectionClass($class);
                if ($reflection->isEnum() || $reflection->isAbstract()) continue;

                $model = $reflection->newInstance();
                if (!method_exists($model, 'getTable')) continue;

                $table = $model->getTable();
                $columns = DB::getSchemaBuilder()->getColumnListing($table);

                $columnData = collect($columns)->map(function ($col) use ($table) {
                    $columnDetails = DB::selectOne(
                        'SELECT is_nullable FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
                        [$table, $col]
                    );
                    $nullable = optional($columnDetails)->is_nullable === 'YES';
                    $type = DB::getSchemaBuilder()->getColumnType($table, $col);
                    return "- `" . ($nullable ? "?" : "") . "$type \$$col`";
                });

                $relationships = $this->getRelationships($class);
                $casts = $this->getCasts($model);

                $markdown .= "## {$reflection->getShortName()}\n\n";
                $markdown .= "### Properties\n" . $columnData->implode("\n") . "\n\n";
                if ($casts->isNotEmpty()) {
                    $markdown .= "### Casts\n" . $casts->implode("\n") . "\n\n";
                }
                if ($relationships->isNotEmpty()) {
                    $markdown .= "### Relationships\n" . $relationships->implode("\n") . "\n\n";
                }
            } catch (\Throwable $e) {
                $this->warn("Skipping $class: " . $e->getMessage());
            }
        }

        File::put(base_path('MODEL_DOCS.md'), $markdown);
        $this->info('Model documentation exported to MODEL_DOCS.md');
    }

    private function getClassFromFile($file)
    {
        $path = $file->getRealPath();
        $contents = file_get_contents($path);
        $namespace = preg_match('/namespace (.*);/', $contents, $matches) ? $matches[1] : null;
        $class = pathinfo($path, PATHINFO_FILENAME);
        return $namespace ? "$namespace\\$class" : $class;
    }

    private function getRelationships($class)
    {
        $refClass = new ReflectionClass($class);
        $instance = new $class;
        $methods = $refClass->getMethods(ReflectionMethod::IS_PUBLIC);

        $annotations = collect();

        foreach ($methods as $method) {
            if ($method->class !== $refClass->getName()) continue;
            if ($method->getNumberOfParameters() > 0) continue;

            try {
                $return = $method->invoke($instance);
                if ($return instanceof Relation) {
                    $related = get_class($return->getRelated());
                    $relatedShort = class_basename($related);
                    $annotations->push("- `\\App\\Models\\$relatedShort \${$method->getName()}`");
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $annotations;
    }

    private function getCasts($model)
    {
        $casts = collect($model->getCasts());

        return $casts->map(function ($cast, $key) {
            $type = is_object($cast) ? get_class($cast) : (is_string($cast) ? $cast : 'mixed');
            return "- `$type \$$key`";
        });
    }
}
