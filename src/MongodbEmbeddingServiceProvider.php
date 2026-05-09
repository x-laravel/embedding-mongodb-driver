<?php

namespace XLaravel\Embedding\Driver\Mongodb;

use Illuminate\Support\ServiceProvider;
use XLaravel\Embedding\SimilarityManager;

class MongodbEmbeddingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'embedding-mongodb-migrations');
        }

        $this->app->resolving(SimilarityManager::class, function (SimilarityManager $manager) {
            $manager->extend('mongodb', fn () => new MongodbDriver());
        });
    }
}
