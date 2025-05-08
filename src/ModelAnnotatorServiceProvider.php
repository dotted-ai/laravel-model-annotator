<?php

namespace DottedAI\ModelAnnotator;

use Illuminate\Support\ServiceProvider;
use DottedAI\ModelAnnotator\Console\Commands\AnnotateModelDocs;

class ModelAnnotatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            AnnotateModelDocs::class,
        ]);
    }
}
