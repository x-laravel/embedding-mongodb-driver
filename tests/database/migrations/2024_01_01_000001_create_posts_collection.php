<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // MongoDB is schemaless — collection is created automatically on first insert
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::connection('mongodb')->table('posts')->raw(
            fn ($collection) => $collection->drop()
        );
    }
};
