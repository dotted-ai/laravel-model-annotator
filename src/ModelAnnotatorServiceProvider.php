<?php

namespace DottedAI\ModelAnnotator;

use Illuminate\Support\ServiceProvider;
use DottedAI\ModelAnnotator\Console\Commands\AnnotateModelDocs;
use DottedAI\ModelAnnotator\Console\Commands\GenerateAnnotationMarkdown;

class ModelAnnotatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            AnnotateModelDocs::class,
            GenerateAnnotationMarkdown::class,
        ]);
    }
}
