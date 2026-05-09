<?php

namespace XLaravel\Embedding\Driver\Mongodb;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use XLaravel\Embedding\Contracts\SimilarityDriver;

class MongodbDriver implements SimilarityDriver
{
    public function search(Model $prototype, array $queryVector, int $limit, float $threshold = 0.0, ?array $ids = null, string $slot = 'default'): Collection
    {
        $embeddingClass = config('embedding.model');
        $morphClass = $prototype->getMorphClass();

        $filter = ['embeddable_type' => $morphClass, 'slot' => $slot];

        if ($ids !== null) {
            $filter['embeddable_id'] = ['$in' => $ids];
        }

        $pipeline = [
            [
                '$vectorSearch' => [
                    'index' => 'vector_index',
                    'path' => 'vector',
                    'queryVector' => $queryVector,
                    'filter' => $filter,
                    'numCandidates' => $limit * 15,
                    'limit' => $limit,
                ],
            ],
            [
                '$addFields' => [
                    'similarity_score' => ['$meta' => 'vectorSearchScore'],
                ],
            ],
        ];

        if ($threshold > 0.0) {
            $pipeline[] = ['$match' => ['similarity_score' => ['$gte' => $threshold]]];
        }

        $results = app($embeddingClass)->raw(fn ($collection) => $collection->aggregate($pipeline));

        $matchedIds = [];
        $scores = [];

        foreach ($results as $result) {
            $id = (string) $result['embeddable_id'];
            $matchedIds[] = $result['embeddable_id'];
            $scores[$id]    = (float) $result['similarity_score'];
        }

        return $prototype::findMany($matchedIds)
            ->each(fn ($m) => $m->setAttribute('similarity_score', $scores[(string) $m->getKey()] ?? 0.0))
            ->sortByDesc(fn ($m) => $m->getAttribute('similarity_score'))
            ->values();
    }
}
