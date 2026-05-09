<?php

namespace XLaravel\Embedding\Driver\Mongodb\Tests;

use Laravel\Ai\AiServiceProvider;
use Laravel\Ai\Embeddings;
use MongoDB\Laravel\MongoDBServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use XLaravel\Embedding\EmbeddingServiceProvider;
use XLaravel\Embedding\Driver\Mongodb\MongodbEmbeddingServiceProvider;
use XLaravel\Embedding\Driver\Mongodb\Models\MongodbEmbedding;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Embeddings::fake();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            MongoDBServiceProvider::class,
            AiServiceProvider::class,
            EmbeddingServiceProvider::class,
            MongodbEmbeddingServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'mongodb');
        $app['config']->set('database.connections.mongodb', [
            'driver'   => 'mongodb',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', 27017),
            'database' => env('DB_DATABASE', 'embedding_test'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'password'),
        ]);

        $app['config']->set('ai.default', 'openai');
        $app['config']->set('ai.providers.openai', [
            'driver'  => 'openai',
            'api_key' => 'fake-api-key-for-testing',
        ]);
        $app['config']->set('ai.default_for_embeddings', 'openai');

        $app['config']->set('embedding.database.connection', 'mongodb');
        $app['config']->set('embedding.queue.connection', 'sync');
        $app['config']->set('embedding.similarity.driver', 'php');
        $app['config']->set('embedding.model', MongodbEmbedding::class);
    }
}
