<?php

namespace Staudenmeir\LaravelMergedRelations;

use Illuminate\Support\ServiceProvider;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Schema::class, function ($app) {
            return Schema::getSchemaBuilder(
                $app['db']->connection()
            );
        });
    }
}
