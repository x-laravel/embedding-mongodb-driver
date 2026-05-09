<?php

namespace XLaravel\Embedding\Driver\Mongodb\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use XLaravel\Embedding\Models\Embedding;

class MongodbEmbedding extends Embedding
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setConnection(config('embedding.database.connection'));
        $this->setTable(config('embedding.database.table'));
    }

    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }
}
