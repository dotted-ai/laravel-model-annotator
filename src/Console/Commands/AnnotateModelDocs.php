<?php

namespace DottedAI\ModelAnnotator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Casts\Castable;
use ReflectionClass;
use ReflectionMethod;

class AnnotateModelDocs extends Command
{
    protected $signature = 'models:annotate';
    protected $description = 'Add database column, relationship, and casting annotations above each Eloquent model';

    public function handle()
    {
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);

        foreach ($files as $file) {
            $class = $this->getClassFromFile($file);
            if (!class_exists($class)) continue;

            $model = new $class;
            if (!method_exists($model, 'getTable')) continue;

            $table = $model->getTable();

            try {
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                $columnData = collect($columns)->map(function ($col) use ($table) {
                    $type = DB::getSchemaBuilder()->getColumnType($table, $col);
                    $nullable = DB::select("SHOW COLUMNS FROM `$table` WHERE Field = ?", [$col])[0]->Null === 'YES';
                    return "@property " . ($nullable ? "?" : "") . "$type \$$col";
                });

                $relationships = $this->getRelationships($class);
                $casts = $this->getCasts($model);

                $annotations = $columnData->merge($relationships)->merge($casts);

                $this->annotateFile($file->getPathname(), $annotations);
                $this->info("Annotated: $class");
            } catch (\Exception $e) {
                $this->warn("Skipping $class: " . $e->getMessage());
            }
        }
    }

    private function getClassFromFile($file)
    {
        $path = $file->getRealPath();
        $contents = file_get_contents($path);
        $namespace = preg_match('/namespace (.*);/', $contents, $matches) ? $matches[1] : null;
        $class = pathinfo($path, PATHINFO_FILENAME);
        return $namespace ? "$namespace\\$class" : $class;
    }

    private function annotateFile($filePath, $annotations)
    {
        $contents = File::get($filePath);
        $contents = preg_replace('/\/\*\*\n(\s*\*.*\n)*\s*\*\//', '', $contents); // remove old annotation

        $docBlock = "/**\n" . $annotations->map(fn($a) => " * $a")->implode("\n") . "\n */\n";
        $contents = preg_replace('/<\?php/', "<?php\n\n$docBlock", $contents, 1);

        File::put($filePath, $contents);
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
                    $annotations->push("@property \\App\\Models\\$relatedShort \${$method->getName()}");
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
            if (is_object($cast)) {
                $type = get_class($cast);
            } elseif (is_string($cast)) {
                $type = $cast;
            } else {
                $type = 'mixed';
            }
            return "@property $type \$$key";
        });
    }
}
