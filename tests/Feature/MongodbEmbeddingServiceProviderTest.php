<?php

namespace XLaravel\Embedding\Driver\Mongodb\Tests\Feature;

use XLaravel\Embedding\Driver\Mongodb\Models\MongodbEmbedding;
use XLaravel\Embedding\Driver\Mongodb\MongodbDriver;
use XLaravel\Embedding\Driver\Mongodb\Tests\Fixtures\Models\Post;
use XLaravel\Embedding\Driver\Mongodb\Tests\TestCase;
use XLaravel\Embedding\SimilarityManager;

class MongodbEmbeddingServiceProviderTest extends TestCase
{
    public function test_it_registers_the_mongodb_driver(): void
    {
        $manager = app(SimilarityManager::class);

        $this->assertInstanceOf(MongodbDriver::class, $manager->driver('mongodb'));
    }

    public function test_it_uses_mongodb_embedding_model(): void
    {
        $this->assertSame(MongodbEmbedding::class, config('embedding.model'));
    }

    public function test_embedding_collection_name_comes_from_config(): void
    {
        $embedding = new MongodbEmbedding();

        $this->assertSame(config('embedding.database.table'), $embedding->getTable());
    }

    public function test_it_stores_and_reads_embedding_as_native_array(): void
    {
        $post = Post::create(['title' => 'Laravel', 'body' => 'PHP Framework']);

        $this->assertNotNull($post->fresh()->embedding);
        $this->assertIsArray($post->fresh()->embedding->vector);
    }
}
