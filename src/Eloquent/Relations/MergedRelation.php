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
        $results = ! is_null($this->getParentKey())
            ? $this->get()
            : $this->related->newCollection();

        foreach ($results as $result) {
            unset($result->laravel_foreign_key);
        }

        return $results;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        $builder = $this->prepareQueryBuilder($columns);

        $models = $builder->getModels();

        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }

    /**
     * Execute the query and get the first related model.
     *
     * @param array $columns
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? $results->first() : null;
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->query->addSelect(
            $this->shouldSelect($columns)
        );

        return $this->query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Prepare the query builder for query execution.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function prepareQueryBuilder($columns = ['*'])
    {
        $builder = $this->query->applyScopes();

        $columns = $builder->getQuery()->columns ? [] : $columns;

        return $builder->addSelect(
            $this->shouldSelect($columns)
        );
    }

    /**
     * Get the select columns for the relation query.
     *
     * @param array $columns
     * @return array
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        if ($columns === ['*']) {
            return $columns;
        }

        return array_merge(
            $columns,
            ['laravel_foreign_key', 'laravel_model', 'laravel_placeholders', 'laravel_with']
        );
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $models
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
