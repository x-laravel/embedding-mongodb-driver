# CLAUDE.md — embedding-mongodb-driver

This file provides guidance to Claude Code (claude.ai/code) when working with this repository.

## Overview

MongoDB vector driver for `x-laravel/embedding`. Provides a MongoDB-native Embedding model and an Atlas Vector Search similarity driver.

- **Package name:** `x-laravel/embedding-mongodb-driver` — **Namespace:** `XLaravel\Embedding\Driver\Mongodb`
- PHP `^8.3` + `ext-mongodb`, Laravel (illuminate) `^12.0|^13.0`, `x-laravel/embedding ^1.0`, `mongodb/laravel-mongodb ^4.0`
- MongoDB 8.0+ (community) or MongoDB Atlas (for native vector search)
- Dev: Orchestra Testbench `^10.0|^11.0`, PHPUnit `^11.0|^12.0`

## Running Tests

```bash
# Build once per PHP version
DOCKER_BUILDKIT=0 docker compose --profile php83 build

# Run all tests
docker compose --profile php83 up   # PHP 8.3
docker compose --profile php84 up   # PHP 8.4
docker compose --profile php85 up   # PHP 8.5

# Run a single test class or method
docker compose --profile php83 run --rm php83 vendor/bin/phpunit --filter MongodbDriverTest
docker compose --profile php83 run --rm php83 vendor/bin/phpunit --filter test_identical_vector_returns_score_of_one
```

Tests use community MongoDB with `PhpDriver` for similarity search. The `mongodb` driver (Atlas `$vectorSearch`) requires MongoDB Atlas and cannot be tested in standard CI. CI runs PHP 8.3–8.5 via `.github/workflows/tests.yml`.

## Source Files (`src/`)

| File | Responsibility |
|------|----------------|
| `Models/MongodbEmbedding.php` | MongoDB-native Eloquent model. Extends `MongoDB\Laravel\Eloquent\Model`. Reads connection and collection name from `embedding.database.*` config. No `json` cast needed — MongoDB stores vectors as native BSON arrays. |
| `MongodbDriver.php` | Implements `SimilarityDriver`. Builds `$vectorSearch` aggregation pipeline for MongoDB Atlas, sets `similarity_score` from `vectorSearchScore` metadata. |
| `MongodbEmbeddingServiceProvider.php` | `boot()` registers `mongodb` similarity driver, loads migration, publishes under `embedding-mongodb-migrations` tag. |

## Test Structure (`tests/`)

| Path | Purpose |
|------|---------|
| `TestCase.php` | Base test case. Boots `EmbeddingServiceProvider` + `MongodbEmbeddingServiceProvider`, sets up MongoDB connection from env vars, sets `embedding.model` to `MongodbEmbedding`, uses `PhpDriver` for similarity, calls `Embeddings::fake()`. |
| `Models/Post.php` | Fixture model extending `MongoDB\Laravel\Eloquent\Model` using `#[EmbedOn]` and `Embeddable` trait. |
| `database/migrations/` | No-op migration — MongoDB is schemaless, collection created on first insert. |
| `Feature/MongodbDriverTest.php` | Tests similarity search via `PhpDriver` with MongoDB backend — verifies vectors are stored/retrieved correctly as native arrays. |
| `Feature/MongodbEmbeddingServiceProviderTest.php` | Tests driver registration, model config, collection name from config, and embedding storage/retrieval. |

## Driver Lifecycle

```
boot()
  ├─► loadMigrationsFrom(...)
  ├─► publishes([...], 'embedding-mongodb-migrations')
  └─► SimilarityManager::extend('mongodb', fn() => new MongodbDriver())
```

No `register()` override — no `VectorStore` binding needed. `JsonVectorStore` works with MongoDB: it calls `updateOrCreate(['vector' => $array])` which MongoDB stores as a native BSON array.

## Key Design Decisions

**Custom Embedding model:** The core `Embedding` model extends `Illuminate\Database\Eloquent\Model` and uses SQL-specific features. `MongodbEmbedding` extends `MongoDB\Laravel\Eloquent\Model` instead. Users must set `'model' => MongodbEmbedding::class` in `config/embedding.php`.

**Collection name from config:** Constructor calls `setTable(config('embedding.database.table'))` — identical pattern to the core Embedding model. In `mongodb/laravel-mongodb`, `setTable()` sets the collection name.

**No custom VectorStore:** MongoDB stores PHP arrays as BSON arrays natively. `JsonVectorStore::updateOrCreate(['vector' => $array])` works without any SQL function wrapping.

**No global scope:** Unlike MySQL (VECTOR binary) or MariaDB (BLOB binary), MongoDB returns BSON arrays directly as PHP arrays. No conversion needed on read.

**PhpDriver fallback:** Community MongoDB has no native vector distance functions. `PhpDriver` works correctly because `MongodbEmbedding` returns vectors as native PHP arrays.

**Atlas Vector Search:** `MongodbDriver` uses `$vectorSearch` aggregation with a `vector_index` index. The Atlas index must be created manually through the Atlas UI or API before using the `mongodb` driver.

**Migration:** MongoDB is schemaless — no column definitions needed. The migration only creates a unique index on `(embeddable_type, embeddable_id, slot)`.

## Migration

Publish and run the MongoDB migration **instead of** the core `embedding-migrations`:

```bash
composer require x-laravel/embedding-mongodb-driver
php artisan vendor:publish --tag=embedding-mongodb-migrations
php artisan migrate
```

Also update `config/embedding.php`:

```php
'model' => \XLaravel\Embedding\Driver\Mongodb\Models\MongodbEmbedding::class,
```

## Git Commits

Never create a commit unless the user explicitly requests it.

## Why This Package Was Cancelled

The `VectorStore` contract requires `store()` to return `XLaravel\Embedding\Models\Embedding`, which extends `Illuminate\Database\Eloquent\Model` and relies on PDO. MongoDB does not use PDO — `mongodb/laravel-mongodb` provides its own `MongoDB\Laravel\Eloquent\Model` that uses a separate driver stack entirely.

`MongodbEmbedding` must extend `MongoDB\Laravel\Eloquent\Model` to work with MongoDB connections. Because of this, it cannot extend the core `Embedding` class, which means it cannot satisfy the `VectorStore::store()` return type and PHP throws a `TypeError` at runtime.

**Why this also affects other drivers:** Relaxing the return type from `Embedding` to `Illuminate\Database\Eloquent\Model` would require updating `VectorStore`, `JsonVectorStore`, `EmbeddingGenerator`, the `ModelEmbedded` event, and the `store()` method signature in every driver (`MysqlVectorStore`, `MariadbVectorStore`, `OracleVectorStore`). This is a cross-cutting breaking change across the entire ecosystem.

**Path forward:** Relax the return type of `VectorStore::store()` to `Illuminate\Database\Eloquent\Model` across the core package and all drivers, then re-implement this package.
