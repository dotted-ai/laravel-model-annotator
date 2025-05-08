<?php

namespace ModelAnnotator;

use Illuminate\Support\ServiceProvider;
use ModelAnnotator\Console\Commands\AnnotateModels;

class ModelAnnotatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            AnnotateModels::class,
        ]);
    }
}
