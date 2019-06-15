<?php

namespace Staudenmeir\LaravelMergedRelations\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MergedRelation extends HasMany
{
    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        $results = parent::getResults();

        foreach ($results as $result) {
            unset($result->laravel_foreign_key);
        }

        return $results;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array  $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $models = parent::match($models, $results, $relation);

        foreach ($results as $result) {
            unset($result->laravel_foreign_key);
        }

        return $models;
    }
}
