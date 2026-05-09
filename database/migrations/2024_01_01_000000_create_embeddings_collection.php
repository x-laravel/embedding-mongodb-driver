<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('embedding.database.connection');
    }

    public function up(): void
    {
        $table = config('embedding.database.table');

        DB::connection($this->getConnection())
            ->table($table)
            ->raw(fn ($collection) => $collection->createIndex(
                ['embeddable_type' => 1, 'embeddable_id' => 1, 'slot' => 1],
                ['unique' => true, 'name' => 'embeddings_unique']
            ));
    }

    public function down(): void
    {
        DB::connection($this->getConnection())
            ->table(config('embedding.database.table'))
            ->raw(fn ($collection) => $collection->drop());
    }
};
