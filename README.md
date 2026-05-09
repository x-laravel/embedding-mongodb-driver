# x-laravel/embedding — MongoDB Driver

[![Tests](https://github.com/x-laravel/embedding-mongodb-driver/actions/workflows/tests.yml/badge.svg)](https://github.com/x-laravel/embedding-mongodb-driver/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12%20|%2013-red)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE.md)

> **⚠️ This package has been cancelled and is not functional.** See [CLAUDE.md](CLAUDE.md) for details.

MongoDB vector driver for [x-laravel/embedding](https://github.com/x-laravel/embedding).

## How It Works

- Provides `MongodbEmbedding` — a MongoDB-native Eloquent model that stores vectors as BSON arrays, reads the collection name from `embedding.database.table` config
- Implements `SimilarityDriver` — registers as the `mongodb` driver for [MongoDB Atlas Vector Search](https://www.mongodb.com/docs/atlas/atlas-vector-search/) using `$vectorSearch` aggregation
- Community MongoDB (without Atlas): vectors are stored natively as BSON arrays; similarity search falls back to `PhpDriver` automatically

## Requirements

- PHP ^8.3 + `ext-mongodb`
- Laravel ^12.0 | ^13.0
- `x-laravel/embedding ^1.0`
- `mongodb/laravel-mongodb ^4.0`
- MongoDB 8.0+ (community) or MongoDB Atlas (for native vector search)

## Installation

```bash
composer require x-laravel/embedding-mongodb-driver
```

The `MongodbEmbeddingServiceProvider` is auto-discovered and registers the `mongodb` driver automatically.

## Setup

### 1. Configure x-laravel/embedding

Publish the config if you haven't already:

```bash
php artisan vendor:publish --tag=embedding-config
```

Set the MongoDB connection and swap the Embedding model in `config/embedding.php`:

```php
'database' => [
    'connection' => env('EMBEDDINGS_DATABASE_CONNECTION', 'mongodb'),
    'table'      => env('EMBEDDINGS_DB_TABLE', 'embeddings'),
],

// Swap the default SQL model with the MongoDB-native model
'model' => \XLaravel\Embedding\Driver\Mongodb\Models\MongodbEmbedding::class,

'similarity' => [
    'driver' => env('EMBEDDING_SIMILARITY_DRIVER', 'auto'),
],
```

### 2. Configure the MongoDB connection

In `config/database.php`:

```php
'connections' => [
    'mongodb' => [
        'driver'   => 'mongodb',
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', 27017),
        'database' => env('DB_DATABASE', 'myapp'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
    ],
],
```

### 3. Create the embeddings collection

```bash
php artisan migrate
```

Or publish the migration first:

```bash
php artisan vendor:publish --tag=embedding-mongodb-migrations
php artisan migrate
```

This creates a unique index on `(embeddable_type, embeddable_id, slot)`.

### 4. MongoDB Atlas Vector Search (optional)

For native DB-level similarity search, create a Vector Search index on the `embeddings` collection in Atlas with path `vector` and set the similarity driver:

```php
'similarity' => ['driver' => 'mongodb'],
```

> **Note:** Without Atlas, similarity search automatically uses `PhpDriver` (vectors loaded into PHP memory). For large datasets, Atlas Vector Search is strongly recommended.

### 5. Model

Follow the standard `x-laravel/embedding` setup. No MongoDB-specific changes are needed on your models.

```php
use XLaravel\Embedding\Attributes\EmbedOn;
use XLaravel\Embedding\Concerns\Embeddable;
use XLaravel\Embedding\Contracts\HasEmbeddings;

#[EmbedOn(['title', 'body'])]
class Post extends Model implements HasEmbeddings
{
    use Embeddable;

    public function toEmbeddingText(): string
    {
        return $this->title.' '.$this->body;
    }
}
```

## Usage

The driver is transparent — use the standard `x-laravel/embedding` API:

```php
Post::similarToText('web framework', limit: 10);
Post::similarTo($vector, limit: 10, threshold: 0.8);
Post::rankByRelevance($posts, 'web framework');

$post->mostSimilar(limit: 5);
$post->similarityTo($otherPost);
```

All methods set a `similarity_score` float attribute on each returned model.

## Testing

```bash
# Build first (once per PHP version)
DOCKER_BUILDKIT=0 docker compose --profile php83 build

# Run tests
docker compose --profile php83 up
docker compose --profile php84 up
docker compose --profile php85 up
```

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/license/MIT).
